<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Requests;
use EasyWeChat\Foundation\Application;//引入微信服务
use EasyWeChat\Message\Text;
use App\user;
use DB;
class WxController extends Controller
{
    protected $app=null;
    public function __construct(){
        $options = [
            'debug' => true,
            'app_id'  => 'wx013ebb3edd8ee631',         // AppID
            'secret'  => 'bec46cb837fd5b95f03db8799fcc79c7',     // AppSecret
            'token'   => 'weixinkafa',  
            // 'aes_key' => null, // 可选
            'log' => [
            'level' => 'debug',
            'file' => 'E:/xampp/htdocs/fenxiao/public/wechat.log', // XXX: 绝对路径！ ！ ！ ！
            ],
            //...
            'guzzle' => [
            'timeout' => 5.0,
            // 超时时间（秒）
            'verify' => false,
            // 关掉 SSL 认证（强烈不建议！！！）
            ],
        ];
       $this->app = new Application($options);//创建微信应用
    }
   public function index() {
       
        //创建微信服务
        $server = $this->app->server;


        //监听消息和事件
        $server->setMessageHandler(function ($message) {
            if($message->MsgType == 'text'){
                $f = $message->Content;
                  $text = new Text(['content'=>"你发送的是->$f"]);
                  return $text;
            }else if($message->MsgType =='event' && $message->Event == 'subscribe'){           
                  return $this->guanzhu($message);
            }else if($message->MsgType =='event' && $message->Event == 'unsubscribe'){
                return $this->quguan($message);
            }
        });


        $response = $server->serve();
        return $response;
   }


   public function guanzhu($message){
                         //获取获取实例
                         //有关注判断openid查询first两种情况
                         // state 0 改为1 1return
                         //
                         //没有关注 ->有无场景二维码
                         //无  直接添加入库   有 她的上一级的P1添加
                    $user = new User();
                    $userService = $this->app->user;
                    $fans = $userService->get($message->FromUserName);

                    $qrid=false;
                    if($message->EventKey){
                        $qrid = substr($message->EventKey,8);
                    }

                    $us = $user->where('openid',$message->FromUserName)->first();
                        
                    if($us && $us->state == 0){
                                $us->state =1;
                                $us->save;
                            } 
                        
                        if(!$us){                              
                                $user = new User();            
                                $user->openid = $message->FromUserName;
                                $user->subtime=time();
                                $user->name =$fans->nickname;
                            if($qrid){
                                 
                                $par =$user->find($qrid);                                                                                                 
                                $user->p1=$qrid;
                                $user->p2=$par->p1;
                                $user->p3=$par->p2;
                            }
                            $user->save();                          
                            $user->qrimg =  $this->qr($user->uid);
                            $user->save();
                    } 




                            // $user = new User();
                            // $userService = $this->app->user;
                            // $fans = $userService->get($message->FromUserName);
                           
                            // $user->openid = $message->FromUserName;
                            // $user->subtime=time();
                            // $user->name =$fans->nickname;
                            // $user->save();

                            //生成场景二维码
                            // $qrimg = $this->qr($user->uid);
                            // $user->qrimg = $qrimg;
                            // $user->save();
                                                    
                    $text = new Text(['content'=>'欢迎关注撸大师']);
                    return $text;
                     
   }
          public function quguan($message){
            
            $fans = User::where('openid',$message->FromUserName)->first();
            if($fans){
                $fans->state = 0;
                $fans->save();
            }
          }
          public function qr($uid){
                    //创建实例
                    $qrcode = $this->app->qrcode;
                    //创建永久二维码
                    $result = $qrcode->forever($uid);
                    //获取二维码网址
                    $ticket = $result->ticket;
                    
                    $url = $qrcode->url($ticket);
                     // 得到二进制图片内容
                    $content = file_get_contents($url);
                     // 写入文件   
                     $qr =public_path() . $this->mkd().'/qr'.$uid.'.jpg';                 
                    file_put_contents($qr , $content);
                    return $qr;
                    

          }
          protected function mkd() {
                $today = date('/Y/m');
                if( !is_dir( public_path() . $today ) ) {
                mkdir( public_path() . $today , 0777 , true);
                } 
                return $today;
        }
}
