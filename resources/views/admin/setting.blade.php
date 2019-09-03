@extends('admin.master.header')
@section('content')

    @include('formMessage')
    <form style="padding: 20px;" method="POST" action="{{route('siteSetting',['locale'=>session('locale')])}}">
        <input class="form-control" type="hidden" name="_token" value="{{csrf_token()}}">

        <div class="form-group">
            <label for="total_th">Total Th</label>
            <input  class="form-control" name="total_th" id="total_th" type="text" placeholder="{{$setting->total_th}}">
        </div>
        <div class="form-group">
            <label for="usd_per_hash">Usd per hash ($)</label>
            <input  class="form-control" id="usd_per_hash" name="usd_per_hash" type="text" placeholder="{{$setting->usd_per_hash}}">
        </div>
        <div class="form-group">
            <label for="usd_toman">usd_toman (T)</label>
            <input readonly class="form-control" id="usd_toman" type="text" placeholder="{{$setting->usd_toman}}">
        </div>
        <div class="form-group">
            <label for="maintenance_fee_per_th_per_day"> maintenance_fee_per_th_per_day ($)</label>
            <input class="form-control" name="maintenance_fee_per_th_per_day" id="maintenance_fee_per_th_per_day" type="text" placeholder="{{$setting->maintenance_fee_per_th_per_day}}">
        </div>
        <div class="form-group">
            <label for="bitcoin_income_per_month_per_th">bitcoin_income_per_month_per_th (BTC)</label>
            <input class="form-control" id="bitcoin_income_per_month_per_th" name="bitcoin_income_per_month_per_th" type="text"  placeholder="{{$setting->bitcoin_income_per_month_per_th}}">
        </div>
        <div class="form-group">
            <label for="sharing_discount">sharing_discount </label>
            <input class="form-control" id="sharing_discount" name="sharing_discount" type="text"  placeholder="{{$setting->sharing_discount}}">
        </div>

        <div class="form-group">
            <label for="hash_life">hash_life (year)</label>
            <input class="form-control" id="hash_life" name="hash_life" type="number"  placeholder="{{$setting->hash_life}}">
        </div>

        <div class="form-group">
            <label for="minimum_redeem">minimum_redeem (BTC)</label>
            <input class="form-control" id="minimum_redeem" name="minimum_redeem" type="text"  placeholder="{{$setting->minimum_redeem}}">
        </div>

        <div class="form-group">
            <label for="apikey">apikey</label>
            <input class="form-control"  id="apikey" name="apikey" type="text" readonly  placeholder="{{$setting->apikey}}">
        </div>

        <div class="form-group">
            <label for="privatekey">privatekey</label>
            <input class="form-control" readonly id="privatekey" name="privatekey" type="text"  placeholder="{{$setting->privatekey}}">
        </div>

        <div class="form-group">
            <label for="publickey">publickey</label>
            <input class="form-control" readonly id="publickey" name="publickey" type="text"  placeholder="{{$setting->publickey}}">
        </div>

        <div class="form-group">
            <label for="paystar_pin">paystar_pin</label>
            <input class="form-control" readonly id="paystar_pin" name="paystar_pin" type="text"  placeholder="{{$setting->paystar_pin}}">
        </div>

        <div class="form-group">
            <label for="publickey">zarrin_pin</label>
            <input class="form-control" id="zarrin_pin" readonly name="zarrin_pin" type="text"  placeholder="{{$setting->zarrin_pin}}">
        </div>

        <div class="form-group">
            <label for="publickey">zarrin_active</label>
            <input class="form-control" id="zarrin_active" name="zarrin_active" type="text"  placeholder="{{$setting->zarrin_active}}">
        </div>

        <div class="form-group">
            <label for="paystar_active">paystar_active</label>
            <input class="form-control" id="paystar_active" name="paystar_active" type="text"  placeholder="{{$setting->paystar_active}}">
        </div>

        <div class="form-group">
            <label for="alarms">alarms</label>
            <input class="form-control" id="alarms" name="alarms" type="text"  placeholder="{{$setting->alarms}}">
        </div>


        <button type="submit" class="btn btn-primary">ویرایش </button>
    </form>

    
@endsection    