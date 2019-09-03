<?php

namespace App\Http\Controllers\Remote;

use App\AntPool;
use App\AntPoolData;
use App\F2Pool;
use App\F2PoolData;
use App\Http\Helpers;
use App\Jobs\subscriptionMailJob;
use App\RemoteData;
use App\RemoteId;
use App\RemoteTransaction;
use App\RemoteUser;
use App\SlushPool;
use App\User;
use App\VerifyUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Facades\Socialite;
use Stevebauman\Location\Facades\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;

class RemoteController extends Controller
{

    public function __construct()
    {

        $this->middleware('remoteAuth')->except('remoteApi','minerDataApi','getPoolDataApi');
    }

    // Gets Miners data by API
    public function remoteApi(Request $request){

        $requestArr = $request->all();
        if(!isset($requestArr['minersInfo'])){

            return ['code'=>500,'message'=> 'No Info on request'];
        }
        if(!isset($requestArr['id'])){

            return ['code'=>500,'message'=> 'No id on request'];
        }
        $data = $requestArr['minersInfo'];
        $id = $requestArr['id'];
        // ===== removing extra data from miners data
        $counter = count($data);
        for($i=0;$i<count($data);$i++){
            $ip = $data[$i]['ip'];
            for($j= $i+1;$j<$counter;$j++) {
                try {
                    if ($data[$j]['ip'] == $ip) {
                        unset($data[$j]);
                    }
                }catch (\Exception $exception){
                    continue;
                }
            }
            $data = array_values($data);
        }
        // =======
        $remote = new RemoteData();
        $remote->data = serialize($data);
        $farmData = DB::connection('mysql')->table('remote_ids')->where('code',$id)->first();
        if(is_null($farmData)){
            return ['error'=> 404 ,'body'=>'Incorrect Id'];
        }else{
            $remote->remote_id = $farmData->user_id;
            $remote->save();
        }

        return ['code'=>200,'message'=> 'done'];
    }

    // Shows Miners Data
    public function dashboard(){
        $farms = DB::connection('mysql')->table('remote_ids')->where('user_id',Auth::guard('remote')->id())->get();
        $farmDetail = DB::connection('mysql')->table('remote_data')->where('remote_id',Auth::guard('remote')->id())->orderBy('id','desc')->first();
        if(is_null($farmDetail)){

            $total_devices = 0;
            $active_devices = 0;
            $total_th = 0;
        }else{

            $data = unserialize($farmDetail->data);
            $total_th = 0;
            for($i=0;$i<count($data);$i++){
                $data[$i]['totalHashrate'] =  str_replace(',','',$data[$i]['totalHashrate']);
                $hash = (number_format($data[$i]['totalHashrate']/1000,1));
                $total_th = $total_th + $hash;
            }
            $active_devices = count($data);
        }

        return view('remote.panel.dashboard',compact('farms','active_devices','total_th'));
    }

    public function getPoolData(){

        $antpools = Auth::guard('remote')->user()->antpools;
        $f2pools = Auth::guard('remote')->user()->f2pools;
        $slushpools = Auth::guard('remote')->user()->slushpools;
        $pools = [];
    try {

        if (!$f2pools->isEmpty()) {
            foreach ($f2pools as $key => $f2pool) {
                $f2poolInst = new \App\UserPools\F2pool($f2pool);
                $miningData = $f2poolInst->mining();
                $f2poolInst = new F2PoolData();
                $f2poolInst->value_last_day = $miningData['message']['value_last_day'];
                $f2poolInst->balance = $miningData['message']['balance'];
                $f2poolInst->hashes_last_day = number_format($miningData['message']['hashes_last_day'] / 86400 / pow(10, 12), 3);
                $f2poolInst->hash_rate = number_format($miningData['message']['hashrate'] / 86400 / pow(10, 7), 3);
                $f2poolInst->paid = $miningData['message']['paid'];
                $f2poolInst->value = $miningData['message']['value'];
                $f2poolInst->user_id = Auth::guard('remote')->id();
                $f2poolInst->save();
            }
            $f2_pool_data = DB::connection('mysql')->table('f2_pool_data')->orderBy('id','desc')->first();
            $f2_pool_data->type = 'f2pool';
            array_push($pools,$f2_pool_data);
        }
        if (!$antpools->isEmpty()) {
            foreach ($antpools as $key => $antpool) {
                $antpoolInst = new \App\UserPools\Antpool($antpool);
                $miningData = $antpoolInst->mining();
                $hashRateData = $antpoolInst->hashRate();
                $antpoolInst = new AntPoolData();
                $antpoolInst->balance = $miningData['data']['balance'];
                $antpoolInst->value_last_day = $miningData['data']['earn24Hours'];
                $antpoolInst->value = $miningData['data']['earnTotal'];
                $antpoolInst->paid = $miningData['data']['paidOut'];
                $antpoolInst->settleTime = $miningData['data']['settleTime'];
                $antpoolInst->hashes_last_day = number_format($hashRateData['data']['last1d'] / pow(10, 6), 1);
                $antpoolInst->user_id = Auth::guard('remote')->id();
                $antpoolInst->save();
            }
            $ant_pool_data = DB::connection('mysql')->table('ant_pool_data')->orderBy('id','desc')->first();
            $ant_pool_data->type = 'antpool';
            array_push($pools,$ant_pool_data);
        }

        if (!$slushpools->isEmpty()) {
            foreach ($slushpools as $key => $slushpool){

//                $slushpool[$key] = new \App\UserPools\Antpool($slushpool);
            }
        }

        return ['code' => 200, 'message' => $pools];

    }catch (\Exception $exception){
        return ['code' => 500, 'message' => $exception->getMessage()];
    }
    }

    public function getPoolDataApi(Request $request){

        if(!$request->has('id')){

            return ['code'=>500,'message'=>'provide an id'];
        }else{
            $id = $request->id;
            $antpools = RemoteUser::where('id',$id)->first()->antpools;
            $f2pools = RemoteUser::where('id',$id)->first()->f2pools;
            $slushpools = RemoteUser::where('id',$id)->first()->slushpools;
            $pools = [];
            try {

                if (!$f2pools->isEmpty()) {
                    foreach ($f2pools as $key => $f2pool) {
                        $f2poolInst = new \App\UserPools\F2pool($f2pool);
                        $miningData = $f2poolInst->mining();
                        $f2poolInst = new F2PoolData();
                        $f2poolInst->value_last_day = $miningData['message']['value_last_day'];
                        $f2poolInst->balance = $miningData['message']['balance'];
                        $f2poolInst->hashes_last_day = number_format($miningData['message']['hashes_last_day'] / 86400 / pow(10, 12), 3);
                        $f2poolInst->hash_rate = number_format($miningData['message']['hashrate'] / 86400 / pow(10, 7), 3);
                        $f2poolInst->paid = $miningData['message']['paid'];
                        $f2poolInst->value = $miningData['message']['value'];
                        $f2poolInst->user_id = $id;
                        $f2poolInst->save();
                    }
                    $f2_pool_data = DB::connection('mysql')->table('f2_pool_data')->orderBy('id','desc')->first();
                    $f2_pool_data->type = 'f2pool';
                    array_push($pools,$f2_pool_data);
                }
                if (!$antpools->isEmpty()) {
                    foreach ($antpools as $key => $antpool) {
                        $antpoolInst = new \App\UserPools\Antpool($antpool);
                        $miningData = $antpoolInst->mining();
                        $hashRateData = $antpoolInst->hashRate();
                        $antpoolInst = new AntPoolData();
                        $antpoolInst->balance = $miningData['data']['balance'];
                        $antpoolInst->value_last_day = $miningData['data']['earn24Hours'];
                        $antpoolInst->value = $miningData['data']['earnTotal'];
                        $antpoolInst->paid = $miningData['data']['paidOut'];
                        $antpoolInst->settleTime = $miningData['data']['settleTime'];
                        $antpoolInst->hashes_last_day = number_format($hashRateData['data']['last1d'] / pow(10, 6), 1);
                        $antpoolInst->user_id = $id;
                        $antpoolInst->save();
                    }
                    $ant_pool_data = DB::connection('mysql')->table('ant_pool_data')->orderBy('id','desc')->first();
                    $ant_pool_data->type = 'antpool';
                    array_push($pools,$ant_pool_data);
                }

                if (!$slushpools->isEmpty()) {
                    foreach ($slushpools as $key => $slushpool){

//                $slushpool[$key] = new \App\UserPools\Antpool($slushpool);
                    }
                }

                return ['code' => 200, 'message' => $pools];

            }catch (\Exception $exception){
                return ['code' => 500, 'message' => $exception->getMessage()];
            }
        }
    }

    public function minerStatus(){
        $minerData = DB::connection('mysql')->table('remote_data')->orderBy('id','desc')->where('remote_id',Auth::guard('remote')->id())->first();
        $modifiedData = $this->modifyTh(unserialize($minerData->data));
        $minerData->data = serialize($modifiedData);
        return view('remote.panel.minerStatus',compact('minerData'));
    }

    public function hardware(){

        return view('remote.panel.hardware');
    }

    public function tutorials(){

        return view('remote.panel.tutorials');
    }

    public function minerDataApi(Request $request){

        if(!$request->has('id')){

            return ['error'=>500,'message'=>'provide an id'];
        }else{

            $token = $request->id;
            $farmData = RemoteId::where('code',$token)->first();
            if(is_null($farmData)){
                return ['error'=>500,'message'=>'incorrect id'];
            }

            $data = RemoteData::where('remote_id',$farmData->user_id)->orderBy('id','desc')->first()->data;

            $modifiedData = $this->modifyTh(unserialize($data));
            return $modifiedData;
        }
    }

    private function modifyTh($data){

        for($i=0;$i<count($data);$i++){
            $data[$i]['totalHashrate'] =  str_replace(',','',$data[$i]['totalHashrate']);
            $data[$i]['totalHashrate'] = number_format($data[$i]['totalHashrate']/1000,1);
        }
        return $data;
    }

    public function RegisterFarm(Request $request){

        $this->validate($request,['name'=> 'required|alpha_num']);
        $remoteId = new RemoteId();
        $remoteId->name = $request->name;
        $remoteId->code = strtoupper(uniqid());
        $remoteId->user_id = Auth::guard('remote')->id();
        $remoteId->save();
        return redirect()->route('remoteDashboard',['locale'=>App::getLocale()]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function PoolRegister(Request $request){

        if($request->pool == 'antpool'){

           $this->validate($request,['api_key'=>'required','secret'=>'required','user_id'=>'required']);
            $antpool = new Antpool();
            $antpool->user_id = encrypt($request->user_id);
            $antpool->nonce = rand(0,1000);
            $antpool->api_key = encrypt($request->api_key);
            $antpool->secret = encrypt($request->secret);
            $antpool->remote_id = Auth::guard('remote')->id();
            $antpool->save();
        }
        if($request->pool == 'f2pool'){
            $this->validate($request,['username'=>'required']);
            $f2pool = new F2pool();
            $f2pool->username = encrypt($request->username);
            $f2pool->user_id = Auth::guard('remote')->id();
            $f2pool->save();
        }
        if($request->pool == 'slushpool'){
            $this->validate($request,['token'=>'required']);
            $slushpool = new SlushPool();
            $slushpool->token = encrypt($request->token);
            $slushpool->user_id = Auth::guard('remote')->id();
            $slushpool->save();
        }

        Session::flash('message','اطلاعات استخر ثبت شد');
        return redirect()->route('remoteDashboard',['locale'=>App::getLocale()]);
  }

  public function Transactions(){

      $transactions = RemoteTransaction::where('user_id',Auth::guard('remote')->id())->where('status','paid')->get();

      return view('remote.payment.list',compact('transactions'));
  }


}
