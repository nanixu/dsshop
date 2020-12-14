<?php

namespace App\Http\Controllers\v1\Element;

use App\Code;
use App\common\Aliyun;
use App\Models\v1\Distribution;
use App\Models\v1\DistributionLog;
use App\common\RedisService;
use App\Mail\VerificationCode;
use App\Models\v1\MiniProgram;
use App\Models\v1\Good;
use App\Models\v1\GoodIndent;
use App\Models\v1\GoodSku;
use App\Models\v1\MoneyLog;
use App\Models\v1\PaymentLog;
use App\Models\v1\SmsLog;
use App\Models\v1\User;
use App\Models\v1\UserLog;
use App\Models\v1\UserRelation;
use App\Notifications\Common;
use App\Notifications\InvoicePaid;
use Carbon\Carbon;
use EasyWeChat\Factory;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Webpatser\Uuid\Uuid;


class WeChatController  extends Controller
{
    /**
     * 处理微信的请求消息
     *
     * @param Request $request
     * @return string
     * @throws \EasyWeChat\Kernel\Exceptions\BadRequestException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \ReflectionException
     */
    public function serve(Request $request)
    {
        if(!$request->has('secret')){
            return resReturn(0,'非法操作',Code::CODE_MISUSE);
        }
        $config = config('wechat.official_account');
        $app = Factory::miniProgram($config);
        $app->server->push(function($message){
            switch ($message['MsgType']) {
                case 'event':
                    return '收到事件消息';
                    break;
                case 'text':
                    return '收到文字消息';
                    break;
                case 'image':
                    return '收到图片消息';
                    break;
                case 'voice':
                    return '收到语音消息';
                    break;
                case 'video':
                    return '收到视频消息';
                    break;
                case 'location':
                    return '收到坐标消息';
                    break;
                case 'link':
                    return '收到链接消息';
                    break;
                case 'file':
                    return '收到文件消息';
                // ... 其它消息
                default:
                    return '收到其它消息';
                    break;
            }
        });
        return $app->server->serve();
    }

    //注册
    public function register(Request $request){
        if(!$request->has('cellphone')){
            return resReturn(0,'手机号不能为空',Code::CODE_WRONG);
        }
        if(!$request->has('password')){
            return resReturn(0,'密码不能为空',Code::CODE_WRONG);
        }
        if(!$request->has('rPassword')){
            return resReturn(0,'重复密码不能为空',Code::CODE_WRONG);
        }
        if($request->password != $request->rPassword){
            return resReturn(0,'重复密码和密码不相同',Code::CODE_WRONG);
        }
        $user=User::where('cellphone',$request->cellphone)->first();
        if($user){
            if($user->unsubscribe == User::USER_UNSUBSCRIBE_YES){
                return resReturn(0,'您的账号已注销，无法重新注册',Code::CODE_WRONG);
            }
            return resReturn(0,'手机号已被注册',Code::CODE_WRONG);
        }
        $redis = new RedisService();
        $code=$redis->get('code.register.'.$request->cellphone);
        if(!$code){
            return resReturn(0,'验证码已失效，请重新获取',Code::CODE_MISUSE);
        }
        if($code !=$request->code){
            return resReturn(0,'验证码错误',Code::CODE_MISUSE);
        }
        $return=DB::transaction(function ()use($request){
            $addUser=new User();
            $addUser->name = $request->cellphone;
            $addUser->cellphone = $request->cellphone;
            $addUser->password=bcrypt($request->password);
            $addUser->api_token = hash('sha256', Str::random(60));
            $addUser->uuid = (string) Uuid::generate();
            $addUser->save();
            // 注册奖励规则获取
            $Distribution=Distribution::where('state',Distribution::DISTRIBUTION_STATE_OPEN)->where('identification',Distribution::DISTRIBUTION_IDENTIFICATION_REGISTRATION__CASH)
                ->with(['DistributionRule'])->first();
            try{    // 防止未按后台录入格式入库的脏数据产生的异常
                if($Distribution->DistributionRule[0]->type == Distribution::DISTRIBUTION_TYPE_FIXED_AMOUNT){
                    $price=$Distribution->DistributionRule[0]->price;
                }else{
                    $price=0;   //注册奖励没有参考金额，所以无法按比例奖励，如需按比例，请写死一个固定值
                }
            }catch (\EXception $e){
                return 1;
            }

            // 用户关系绑定
            if($request->has('uuid')){
                $User=User::where('uuid',$request->uuid)->with([ //一级
                    'UserRelation'=>function($q){   //二级
                        $q->where('level',UserRelation::USER_RELATION_LEVEL_ONE)->with(['UserRelation'=>function($q){   //三级
                            $q->where('level',UserRelation::USER_RELATION_LEVEL_ONE);
                        }]);
                    }
                ])->first();
                // 注册奖励处理
                if($Distribution){
                    User::where('id',$User->id)->increment('money',$price);
                    $DistributionLog=new DistributionLog();
                    $DistributionLog->user_id = $User->id;
                    $DistributionLog->children_id = $addUser->id;
                    $DistributionLog->name = $Distribution->name;
                    $DistributionLog->type = $Distribution->DistributionRule[0]->type;
                    $DistributionLog->level = DistributionLog::DISTRIBUTION_LOG_LEVEL_ONE;
                    $DistributionLog->price = $price;
                    $DistributionLog->save();
                    $Money=new MoneyLog();
                    $Money->user_id = $User->id;
                    $Money->type = MoneyLog::MONEY_LOG_TYPE_INCOME;
                    $Money->money = $price;
                    $Money->remark = '邀请奖励，获得'.($price/100).'元';
                    $Money->save();
                    // 通知
                    $invoice=[
                        'type'=> InvoicePaid::NOTIFICATION_TYPE_DEAL,
                        'title'=>'邀请奖励',
                        'list'=>[
                            [
                                'keyword'=>'支付方式',
                                'data'=>'余额支付'
                            ]
                        ],
                        'price'=>$price,
                        'url'=>'/pages/finance/bill_show?id='.$Money->id,
                        'remark'=>'邀请奖励，获得'.($price/100).'元',
                        'prefers'=>['database']
                    ];
                    $user = User::find($User->id);
                    $user->notify(new InvoicePaid($invoice));
                }
                // 一级关系绑定
                $UserRelation = new UserRelation();
                $UserRelation->children_id =$addUser->id;    //注册用户ID
                $UserRelation->parent_id =$User->id;    //一级ID
                $UserRelation->level =UserRelation::USER_RELATION_LEVEL_ONE;
                $UserRelation->save();
                //二级关系绑定
                if($User->UserRelation){
                    $UserRelation = new UserRelation();
                    $UserRelation->children_id =$addUser->id;  //注册用户ID
                    $UserRelation->parent_id =$User->UserRelation->parent_id;  //二级ID
                    $UserRelation->level =UserRelation::USER_RELATION_LEVEL_TWO;
                    $UserRelation->save();
                    //三级关系绑定
                    if($User->UserRelation->UserRelation){
                        $UserRelation = new UserRelation();
                        $UserRelation->children_id =$addUser->id;  //注册用户ID
                        $UserRelation->parent_id =$User->UserRelation->UserRelation->parent_id;  //三级ID
                        $UserRelation->level =UserRelation::USER_RELATION_LEVEL_THREE;
                        $UserRelation->save();
                    }
                }
            }
            return 1;
        }, 5);
        if($return == 1){
            return resReturn(1,'注册成功');
        }else{
            return resReturn(0,'注册失败',Code::CODE_PARAMETER_WRONG);
        }
    }

    //找回密码
    public function findPassword(Request $request){
        if(!$request->has('cellphone')){
            return resReturn(0,'手机号不能为空',Code::CODE_WRONG);
        }
        if(!$request->has('password')){
            return resReturn(0,'新密码不能为空',Code::CODE_WRONG);
        }
        if(!$request->has('rPassword')){
            return resReturn(0,'确认密码不能为空',Code::CODE_WRONG);
        }
        if($request->password != $request->rPassword){
            return resReturn(0,'确认密码和新密码不相同',Code::CODE_WRONG);
        }
        $redis = new RedisService();
        $code=$redis->get('code.register.'.$request->cellphone);
        if(!$code){
            return resReturn(0,'验证码已失效，请重新获取',Code::CODE_MISUSE);
        }
        if($code !=$request->code){
            return resReturn(0,'验证码错误',Code::CODE_MISUSE);
        }
        $user=User::where('cellphone',$request->cellphone)->first();
        $user->password=bcrypt($request->password);
        $user->save();
        return resReturn(1,'密码重置成功');
    }

    public function login(Request $request){
        if(!$request->has('cellphone')){
            return resReturn(0,'手机号不能为空',Code::CODE_WRONG);
        }
        if(!$request->has('password')){
            return resReturn(0,'密码不能为空',Code::CODE_WRONG);
        }
        $user=User::where('cellphone',$request->cellphone)->first();
        if(!$user){
            return resReturn(0,'手机号未注册过',Code::CODE_WRONG);
        }
        if($user->unsubscribe == User::USER_UNSUBSCRIBE_YES){
            return resReturn(0,'您的账号已注销，无法重新注册',Code::CODE_WRONG);
        }
        if($user->state == User::USER_STATE_FORBID){
            return resReturn(0,'您的账户禁止访问，请联系管理员',Code::CODE_WRONG);
        }
        if (!Hash::check($request->password, $user->password)) {
            return resReturn(0,'密码错误',Code::CODE_WRONG);
        }
        if(!$user->api_token){
            $user->api_token = hash('sha256', Str::random(60));

        }
        $user->updated_at=Carbon::now()->toDateTimeString();
        $user->save();
        $input = $request->all();
        $log = new UserLog();
        $log->user_id = $user->id;
        $log->path = $request->path();
        $log->method = $request->method();
        $log->ip = $request->ip();
        $log->input = json_encode($input, JSON_UNESCAPED_UNICODE);
        $log->save();   # 记录日志
        return resReturn(1,[
            'nickname'=>$user->nickname,
            'cellphone'=>$user->cellphone,
            'portrait'=>$user->portrait,
            'api_token'=>$user->api_token,
            'wechat'=>$user->wechat
        ]);
    }

    //登出
    public function logout(Request $request){
        $input = $request->all();
        $log = new UserLog();
        $log->user_id = auth('web')->user()->id;
        $log->path = $request->path();
        $log->method = $request->method();
        $log->ip = $request->ip();
        $log->input = json_encode($input, JSON_UNESCAPED_UNICODE);
        $log->save();   # 记录日志
        return resReturn(1,'退出成功');
    }

    //注册手机验证码
    public function getRegisterCellphoneCode(Request $request){
        $redis = new RedisService();
        if(!$request->has('cellphone')){
            return resReturn(0,'手机号不能为空',Code::CODE_WRONG);
        }
        $user=User::where('cellphone',$request->cellphone)->first();
        if(!$request->has('state')){
            if($user){
                return resReturn(0,'手机号已被注册',Code::CODE_WRONG);
            }
        }else{
            if(!$user){
                return resReturn(0,'手机号不存在',Code::CODE_WRONG);
            }
        }
        if($redis->get('code.register.'.$request->cellphone)){
            return resReturn(0,'您的验证码还没有失效，请不要重复获取',Code::CODE_WRONG);
        }
        $code=rand(10000, 99999);
        $redis->setex('code.register.'.$request->cellphone,config('dswjcms.failuretime'),$code);
        $Config = config('sms');
        if(!$Config[$Config['service']]['access_id']){    //没有配置短信账号，直接返回验证码
            return resReturn(1,['code'=>$code]);
        }
        $return=$this->getCode($Config['service'],$request->cellphone,$code);
        if($return['Code'] == "OK"){
            return resReturn(1,'成功');
        }else{
            $redis->del('code.register.'.$request->cellphone);
            return resReturn(0,$return['Message'],Code::CODE_WRONG);
        }
    }

    /**
     * 邮箱验证码
     * @param Request $request
     * @return string
     */
    public function getRegisterEmailCode(Request $request){
        if(!$request->email){
            return resReturn(0,'邮箱不能为空',Code::CODE_WRONG);
        }
        if($request->oldEmail){
            if(auth('web')->user()->email != $request->oldEmail){
                return resReturn(0,'请求参数有误',Code::CODE_MISUSE);
            }
            if(auth('web')->user()->email == $request->email){
                return resReturn(0,'您的邮箱已认证，无需再次验证',Code::CODE_WRONG);
            }
        }
        $user=User::where('email',$request->email)->where('id','!=',auth('web')->user()->id)->first();
        if($user){
            return resReturn(0,'邮箱已被注册',Code::CODE_WRONG);
        }
        if (!filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            return resReturn(0,'邮箱格式有误',Code::CODE_WRONG);
        }

        if(!config('mail.username')){    //没有配置邮箱，直接返回错误
            return resReturn(0,'您的发件邮箱没有配置',Code::CODE_WRONG);
        }
        $redis = new RedisService();
        if($redis->get('code.register.'.$request->email)){
            return resReturn(0,'您的验证码还没有失效，请不要重复获取',Code::CODE_WRONG);
        }
        $code=rand(10000, 99999);
        $redis->setex('code.register.'.$request->email,config('dswjcms.failuretime'),$code);
        Mail::to($request->email)->send(new VerificationCode($code));
        return resReturn(1,'发送成功');
    }

    /**
     * 绑定邮箱
     * @param Request $request
     * @return string
     */
    public function verifyEmail(Request $request){
        $invoice=[
            'prefers'=>['mail']
        ];
        $user = User::find(auth('web')->user()->id);
        $user->notify(new InvoicePaid($invoice));
        exit;
        if(!$request->email){
            return resReturn(0,'邮箱不能为空',Code::CODE_WRONG);
        }
        if($request->oldEmail){
            if(auth('web')->user()->email != $request->oldEmail){
                return resReturn(0,'请求参数有误',Code::CODE_MISUSE);
            }
            if(auth('web')->user()->email == $request->email){
                return resReturn(0,'您的邮箱已绑定，无需再次绑定',Code::CODE_WRONG);
            }
        }
        $redis = new RedisService();
        $code=$redis->get('code.register.'.$request->email);
        if(!$code){
            return resReturn(0,'验证码已失效，请重新获取',Code::CODE_MISUSE);
        }
        if($code !=$request->code){
            return resReturn(0,'验证码错误',Code::CODE_MISUSE);
        }
        $user=User::where('email',$request->email)->where('id','!=',auth('web')->user()->id)->first();
        if($user){
            return resReturn(0,'邮箱已被注册',Code::CODE_WRONG);
        }
        if (!filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            return resReturn(0,'邮箱格式有误',Code::CODE_WRONG);
        }
        $return=DB::transaction(function ()use($request){
            $User=User::find(auth('web')->user()->id);
            $User->email = $request->email;
            $User->save();
            return 1;
        }, 5);
        if($return == 1){
            return resReturn(1,'绑定成功');
        }else{
            return resReturn(0,'绑定失败',Code::CODE_PARAMETER_WRONG);
        }
    }

    /**
     * @param $service //短信商标识
     * @param $cellphone //手机号
     * @param $code //验证码
     * @internal param $applySecret
     * @return string
     * @throws \AlibabaCloud\Client\Exception\ClientException
     */
    protected function getCode($service,$cellphone,$code){
        $Aliyun=new Aliyun();
        $return =$Aliyun->sendCode($cellphone,$code);
        SmsLog::setSmsLog(array(
            'sms_service'=>$service,
            'phone'=>$cellphone,
            'data'=>$return
        ));
        return $return;
    }

    /**
     * 余额支付
     * @param Request $request
     * @return string
     */
    public function balancePay(Request $request){
        if(!$request->id){
            return resReturn(0,'参数有误',Code::CODE_PARAMETER_WRONG);
        }
        $GoodIndent=GoodIndent::with(['goodsList'])->find($request->id);
        $User = User::find(auth('web')->user()->id);
        if($User->money < $GoodIndent->total){  //余额小于需要支付的费用
            return resReturn(0,'账户余额不足，无法完成订单',Code::CODE_PARAMETER_WRONG);
        }
        foreach ($GoodIndent->goodsList as $indentCommodity){
            $Good=Good::select('id','is_inventory','inventory')->find($indentCommodity['good_id']);
            if($Good && $Good->is_inventory == Good::GOOD_IS_INVENTORY_FILM){ //付款减库存
                if(!$indentCommodity['good_sku_id']){ //非SKU商品
                    if($Good->inventory-$indentCommodity['number']<0){
                        return resReturn(0,'存在库存不足的商品，请重新购买',Code::CODE_PARAMETER_WRONG);
                    }
                    $Good->inventory = $Good->inventory-$indentCommodity['number'];
                    $Good->save();
                }else{
                    $GoodSku=GoodSku::find($indentCommodity['good_sku_id']);
                    if($GoodSku->inventory-$indentCommodity['number']<0){
                        return resReturn(0,'存在库存不足的SKU商品，请重新购买',Code::CODE_PARAMETER_WRONG);
                    }
                    $GoodSku->inventory = $GoodSku->inventory-$indentCommodity['number'];
                    $GoodSku->save();
                }
            }
        }
        $return=DB::transaction(function ()use($request,$GoodIndent){
            User::where('id',auth('web')->user()->id)->decrement('money', $GoodIndent->total);
            $GoodIndent->state = GoodIndent::GOOD_INDENT_STATE_DELIVER;
            $GoodIndent->pay_time= Carbon::now()->toDateTimeString();
            $GoodIndent->save();
            $Money=new MoneyLog();
            $Money->user_id = auth('web')->user()->id;
            $Money->type = MoneyLog::MONEY_LOG_TYPE_EXPEND;
            $Money->money = $GoodIndent->total;
            $Money->remark = '对订单：'.$GoodIndent->identification.'的付款';
            $Money->save();
            $Common=(new Common)->finishPayment([
                'money_id'=>$Money->id,  //资金记录ID
                'identification'=>$GoodIndent->identification,  //订单号
                'total'=>$GoodIndent->total,    //订单金额
                'type'=> '余额支付',
                'user_id'=>auth('web')->user()->id    //用户ID
            ]);
            if($Common['result']== 'ok'){
                return array(1,'支付成功');
            }else{
                return array($Common['msg'],Code::CODE_PARAMETER_WRONG);
            }
        });
        if($return[0] == 1){
            return resReturn(1,$return[1]);
        }else{
            return resReturn(0,$return[0],$return[1]);
        }

    }

    /**
     * 小程序换取openid
     * @param Request $request
     * @return string
     * @throws \Exception
     */
    public function miniLogin(Request $request){
        // 不支持的直接返回
        if(!in_array($request->platform,['miniWeixin','miniAlipay','miniToutiao'])){
            return resReturn(1,[]);
        }
        $MiniProgram = new MiniProgram();
        $mini=$MiniProgram->mini($request->platform,$request->code);
        if($mini['result']== 'ok'){
            return resReturn(1,$mini);
        }else{
            return resReturn(0,$mini['msg'],Code::CODE_WRONG);
        }
    }

    /**
     * 授权获取手机号
     * @param Request $request
     * @return array
     */
    public function authorizedPhone(Request $request){
        if($request->has('iv')){
            $openid=$request->header('openid');
            if(!$openid){
                return resReturn(0,'参数有误',Code::CODE_MISUSE);
            }
            $MiniProgram = new MiniProgram();
            $miniPhoneNumber=$MiniProgram->miniPhoneNumber($request->platform,$request->session_key,$request->iv,$request->encryptedData);
            if($miniPhoneNumber['result']== 'error'){
                return resReturn(0,$miniPhoneNumber['msg'],Code::CODE_MISUSE);
            }
            $User = User::where('cellphone',$miniPhoneNumber['purePhoneNumber'])->first();
            if(!$User){
                $return =DB::transaction(function ()use($request,$miniPhoneNumber,$openid){
                    $password = substr(MD5(time()),5,6);  //随机生成密码
                    $user=new User();
                    $user->name = $miniPhoneNumber['purePhoneNumber'];
                    $user->cellphone = $miniPhoneNumber['purePhoneNumber'];
                    $user->password=bcrypt($password);
                    $user[strtolower($request->platform)]=$openid;
                    $user->api_token = hash('sha256', Str::random(60));
                    $user->save();
                    $input = $request->all();
                    $log = new UserLog();
                    $log->user_id = $user->id;
                    $log->path = $request->path();
                    $log->method = $request->method();
                    $log->ip = $request->ip();
                    $log->input = json_encode($input, JSON_UNESCAPED_UNICODE);
                    $log->save();   # 记录日志
                    $Common=(new Common)->register([
                        'phoneNumber'=>$miniPhoneNumber['purePhoneNumber'],  //手机号
                        'password'=>$password,  //密码
                        'user_id'=>$user->id   //用户ID
                    ]);
                    if($Common['result']== 'error'){
                        return [
                            'state'=>0,
                            'msg'=>$Common['msg'],
                            'code'=>Code::CODE_PARAMETER_WRONG
                        ];
                    }
                    return [
                        'state'=>1,
                        'data'=>$user
                    ];
                }, 5);
                if($return['state'] == 1){
                    return resReturn(1,[
                        'nickname'=>$return['data']->nickname,
                        'cellphone'=>$return['data']->cellphone,
                        'portrait'=>$return['data']->portrait,
                        'api_token'=>$return['data']->api_token,
                        'wechat'=>$return['data']->wechat
                    ]);
                }else{
                    return resReturn(0,$return['msg'],$return['code']);
                }
            }else{
                if($User->unsubscribe == User::USER_UNSUBSCRIBE_YES){
                    return resReturn(0,'您的账号已注销，无法重新注册',Code::CODE_WRONG);
                }
                $return =DB::transaction(function ()use($request,$miniPhoneNumber,$User,$openid){
                    $User->updated_at=Carbon::now()->toDateTimeString();
                    if(!$User[strtolower($request->platform)]){
                        $User[strtolower($request->platform)]=$openid;
                    }
                    $User->save();
                    $input = $request->all();
                    $log = new UserLog();
                    $log->user_id = $User->id;
                    $log->path = $request->path();
                    $log->method = $request->method();
                    $log->ip = $request->ip();
                    $log->input = json_encode($input, JSON_UNESCAPED_UNICODE);
                    $log->save();   # 记录日志
                    return [
                        'state'=>1,
                        'data'=>$User
                    ];
                }, 5);
                if($return['state'] == 1){
                    return resReturn(1,[
                        'nickname'=>$return['data']->nickname,
                        'cellphone'=>$return['data']->cellphone,
                        'portrait'=>$return['data']->portrait,
                        'api_token'=>$return['data']->api_token,
                        'wechat'=>$return['data']->wechat
                    ]);
                }
            }
        }else{
            return resReturn(0,'您拒绝授权，无法登录',Code::CODE_MISUSE);
        }
    }

    /**
     * 统一在线支付
     * @param Request $request
     * @return string
     */
    public function unifiedPayment(Request $request){
        $reutn=[];
        switch ($request->type){
            case PaymentLog::PAYMENT_LOG_TYPE_GOODS_INDENT:  //商品订单支付
                $reutn = (new GoodIndent())->payment($request);
                break;
        }
        if($reutn['result']=='ok'){
            return resReturn(1,$reutn);
        }else{
            return resReturn(0,$reutn['msg'],Code::CODE_WRONG);
        }
    }

    /**
     * 微信支付回调
     * @param Request $request
     * @return string
     * @throws \EasyWeChat\Kernel\Exceptions\Exception
     */
    public function paymentNotify(Request $request){
        $config = config('wechat.payment.default');
        $app = Factory::payment($config);
        $response = $app->handlePaidNotify(function ($message, $fail)
        {
            // 根据返回的订单号查询订单数据
            $order = PaymentLog::where('number',$message['out_trade_no'])->first();
            if (!$order || $order->state  > PaymentLog::PAYMENT_LOG_STATE_CREATE) {
                return true;
            }
            // 支付成功后的业务逻辑
            if ($message['return_code'] === 'SUCCESS') { // return_code 表示通信状态，不代表支付状态
                // 用户是否支付成功
                if ($message['result_code'] === 'SUCCESS') {
//                    $PaymentLog = PaymentLog::find($order->id);
                    $order->state = PaymentLog::PAYMENT_LOG_STATE_COMPLETE;
                    $order->transaction_id = $message['transaction_id'];
                    $order->data = json_encode($message);
                    $order->save();
                    switch ($order->type){
                        case PaymentLog::PAYMENT_LOG_TYPE_GOODS_INDENT:  //商品订单支付
                            (new GoodIndent())->goodIndentNotify($order['pay_id']);
                            break;
                    }
                    // 用户支付失败
                } elseif ($message['result_code'] === 'FAIL') {
                    $order->state = PaymentLog::PAYMENT_LOG_STATE_FAILURE;
                    $order->transaction_id = $message['transaction_id'];
                    $order->data = json_encode($message);
                    $order->save();
                }
            } else {
                return $fail('Order not exists.');
            }
            return true;
        });
        return $response;
    }

    /**
     * 微信支付退款回调
     * @param Request $request
     * @return string
     * @throws \EasyWeChat\Kernel\Exceptions\Exception
     */
    public function refundNotify(Request $request){
//        Log::info('退款:'.json_encode($request->all()));
        $config = config('wechat.payment.default');
        $app = Factory::payment($config);
        $response = $app->handleRefundedNotify(function ($message, $reqInfo, $fail)
        {

            // 根据返回的订单号查询订单数据
            $PaymentLog = PaymentLog::where('number',$reqInfo['out_trade_no'])->first();
            if (!$PaymentLog || $PaymentLog->state > PaymentLog::PAYMENT_LOG_STATE_CREATE) {
                return true;
            }
            // 支付成功后的业务逻辑
            if ($message['return_code'] === 'SUCCESS') {
                if ($message['return_code'] === 'SUCCESS') {
                    $PaymentLog->state = PaymentLog::PAYMENT_LOG_STATE_COMPLETE;
                    $PaymentLog->transaction_id = $reqInfo['transaction_id'];
                    $PaymentLog->data = json_encode($reqInfo);
                    $PaymentLog->save();
                    switch ($PaymentLog->type){
                        case PaymentLog::PAYMENT_LOG_TYPE_REFUND:
                            (new GoodIndent())->goodIndentRefundNotify($PaymentLog['pay_id']);
                            break;
                    }
                    // 用户支付失败
                } elseif ($message['result_code'] === 'FAIL') {
                    $PaymentLog->state = PaymentLog::PAYMENT_LOG_STATE_FAILURE;
                    $PaymentLog->transaction_id = $message['transaction_id'];
                    $PaymentLog->data = json_encode($message);
                    $PaymentLog->save();
                }
            } else {
                return $fail('Order not exists.');
            }
            return true;
        });
        return $response;
    }
}
