<?php
namespace App\Http\Controllers\Pickup;

use App\Http\Controllers\AdminController;

use App\Models\Shipment;
use App\Models\Device;
use App\Models\History;
use App\Models\Shipmentlog;

use App\Helpers\Prefs;

use Creitive\Breadcrumbs\Breadcrumbs;

use Config;

use Auth;
use Event;
use View;
use Input;
use Request;
use Response;
use Mongomodel;
use \MongoRegex;
use \MongoDate;
use DB;
use HTML;

class ZoningController extends AdminController {

    public function __construct()
    {
        parent::__construct();

        $cname = substr(strrchr(get_class($this), '\\'), 1);
        $this->controller_name = str_replace('Controller', '', $cname);


        //$this->crumb = new Breadcrumb();
        //$this->crumb->append('Home','left',true);
        //$this->crumb->append(strtolower($this->controller_name));

        $this->model = new Shipment();
        //$this->model = DB::collection('documents');
        $this->title = 'Device Zone Assignment';

    }

    public function getIndex()
    {


        $this->heads = config('jex.default_zoning_heads');

        //print $this->model->where('docFormat','picture')->get()->toJSON();

        $this->title = 'Device Zone Assignment';

        $this->place_action = 'first';

        $this->show_select = true;

        $this->crumb->addCrumb('Shipment Order',url( strtolower($this->controller_name) ));


        //$this->additional_filter = View::make(strtolower($this->controller_name).'.addfilter')->with('submit_url','gl')->render();
        $this->additional_filter = View::make('incoming.addfilter')
                        ->with('submit_url','gl')
                        ->with('ajaxawbdlxl','incoming/awbdlxl')
                        ->with('importawburl','incoming/importawb')
                        ->render();

        $this->additional_filter .= View::make(strtolower($this->controller_name).'.addfilter')
            ->with('ajaxdeviceurl',strtolower($this->controller_name).'/shipmentlist')->render();

        $this->additional_filter .= View::make('shared.cancelaction')->render();

        //$this->additional_filter .= View::make('shared.confirmaction')->render();

        $this->additional_filter .= '<br />';
        $this->additional_filter .= View::make('shared.markaction')->render();

        //$this->js_additional_param = "aoData.push( { 'name':'acc-period-to', 'value': $('#acc-period-to').val() }, { 'name':'acc-period-from', 'value': $('#acc-period-from').val() }, { 'name':'acc-code-from', 'value': $('#acc-code-from').val() }, { 'name':'acc-code-to', 'value': $('#acc-code-to').val() }, { 'name':'acc-company', 'value': $('#acc-company').val() } );";

        $this->product_info_url = strtolower($this->controller_name).'/info';

        $this->column_styles = '{ "sClass": "column-amt", "aTargets": [ 8 ] },
                    { "sClass": "column-amt", "aTargets": [ 9 ] },
                    { "sClass": "column-amt", "aTargets": [ 10 ] }';

        $this->can_add = false;
        $this->can_import = false;
        $this->can_export = false;

        return parent::getIndex();

    }

    public function postIndex()
    {

        $this->fields = config('jex.default_zoning_fields');

        /*
        $categoryFilter = Request::input('categoryFilter');
        if($categoryFilter != ''){
            $this->additional_query = array('shopcategoryLink'=>$categoryFilter, 'group_id'=>4);
        }
        */

        $db = config('jayon.main_db');

        $this->def_order_by = 'ordertime';
        $this->def_order_dir = 'desc';
        $this->place_action = 'first';
        $this->show_select = true;

        $this->sql_key = 'delivery_id';
        $this->sql_table_name = config('jayon.incoming_delivery_table');
        $this->sql_connection = 'mysql';

        return parent::SQLtableResponder();
    }

    public function getStatic()
    {

        $this->heads = array(
            array('Timestamp',array('search'=>true,'sort'=>true, 'style'=>'min-width:90px;','daterange'=>true)),
            array('PU Time',array('search'=>true,'sort'=>true, 'style'=>'min-width:100px;','daterange'=>true)),
            array('PU Pic',array('search'=>true,'sort'=>true, 'style'=>'min-width:120px;')),
            array('PU Person & Device',array('search'=>true,'style'=>'min-width:100px;','sort'=>true)),
            array('Requested Delivery Date',array('search'=>true,'style'=>'min-width:125px;','sort'=>true, 'daterange'=>true )),
            array('Requested Slot',array('search'=>true,'sort'=>true)),
            array('Zone',array('search'=>true,'sort'=>true)),
            array('City',array('search'=>true,'sort'=>true)),
            array('Shipping Address',array('search'=>true,'sort'=>true, 'style'=>'max-width:200px;width:200px;' )),
            array('No Invoice',array('search'=>true,'sort'=>true)),
            array('Type',array('search'=>true,'sort'=>true)),
            array('Merchant & Shop Name',array('search'=>true,'sort'=>true)),
            array('Box ID',array('search'=>true,'sort'=>true)),
            array('Status',array('search'=>true,'sort'=>true)),
            array('Directions',array('search'=>true,'sort'=>true)),
            array('Signatures',array('search'=>true,'sort'=>true)),
            array('Delivery Charge',array('search'=>true,'sort'=>true)),
            array('COD Surcharge',array('search'=>true,'sort'=>true)),
            array('COD Value',array('search'=>true,'sort'=>true)),
            array('Buyer',array('search'=>true,'sort'=>true)),
            array('ZIP',array('search'=>true,'sort'=>true)),
            array('Phone',array('search'=>true,'sort'=>true)),
            array('W x H x L = V',array('search'=>true,'sort'=>true)),
            array('Weight Range',array('search'=>true,'sort'=>true)),
        );

        //print $this->model->where('docFormat','picture')->get()->toJSON();

        $this->title = 'General Ledger';


        $this->crumb->addCrumb('Cost Report',url( strtolower($this->controller_name) ));

        //$this->additional_filter = View::make(strtolower($this->controller_name).'.addfilter')->with('submit_url','gl/static')->render();

        //$this->js_additional_param = "aoData.push( { 'name':'acc-period-to', 'value': $('#acc-period-to').val() }, { 'name':'acc-period-from', 'value': $('#acc-period-from').val() }, { 'name':'acc-code-from', 'value': $('#acc-code-from').val() }, { 'name':'acc-code-to', 'value': $('#acc-code-to').val() }, { 'name':'acc-company', 'value': $('#acc-company').val() } );";

        $this->product_info_url = strtolower($this->controller_name).'/info';

        $this->printlink = strtolower($this->controller_name).'/print';

        //table generator part

        $this->fields = array(
            array('ordertime',array('kind'=>'daterange', 'query'=>'like','pos'=>'both','show'=>true)),
            array('pickuptime',array('kind'=>'daterange', 'query'=>'like','pos'=>'both','show'=>true)),
            array('pickup_person',array('kind'=>'text', 'query'=>'like','pos'=>'both','show'=>true)),
            array('pickup_person',array('kind'=>'text', 'query'=>'like','pos'=>'both','show'=>true)),
            array('buyerdeliverytime',array('kind'=>'daterange','query'=>'like','pos'=>'both','show'=>true)),
            array('buyerdeliveryslot',array('kind'=>'text' , 'query'=>'like', 'pos'=>'both','show'=>true)),
            array('buyerdeliveryzone',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('buyerdeliverycity',array('kind'=>'text', 'query'=>'like','pos'=>'both','show'=>true)),
            array('shipping_address',array('kind'=>'text', 'query'=>'like','pos'=>'both','show'=>true)),
            array('merchant_trans_id',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('delivery_type',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array(config('jayon.jayon_members_table').'.merchantname',array('kind'=>'text','alias'=>'merchant_name','query'=>'like','callback'=>'merchantInfo','pos'=>'both','show'=>true)),
            array('delivery_id',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('status',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('directions',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('delivery_id',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('delivery_cost',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('cod_cost',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('total_price',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('buyer_name',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('shipping_zip',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('phone',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('volume',array('kind'=>'numeric','query'=>'like','pos'=>'both','show'=>true)),
            array('weight',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
        );

        $db = config('jayon.main_db');

        $this->def_order_by = 'ordertime';
        $this->def_order_dir = 'desc';
        $this->place_action = 'none';
        $this->show_select = false;

        $this->sql_key = 'delivery_id';
        $this->sql_table_name = config('jayon.incoming_delivery_table');
        $this->sql_connection = 'mysql';

        $this->responder_type = 's';

        return parent::printGenerator();
    }

    public function getPrint()
    {

        $this->heads = array(
            array('Timestamp',array('search'=>true,'sort'=>true, 'style'=>'min-width:90px;','daterange'=>true)),
            array('PU Time',array('search'=>true,'sort'=>true, 'style'=>'min-width:100px;','daterange'=>true)),
            array('PU Pic',array('search'=>true,'sort'=>true, 'style'=>'min-width:120px;')),
            array('PU Person & Device',array('search'=>true,'style'=>'min-width:100px;','sort'=>true)),
            array('Delivery Date',array('search'=>true,'style'=>'min-width:125px;','sort'=>true, 'daterange'=>true )),
            array('Slot',array('search'=>true,'sort'=>true)),
            array('Zone',array('search'=>true,'sort'=>true)),
            array('City',array('search'=>true,'sort'=>true)),
            array('Shipping Address',array('search'=>true,'sort'=>true, 'style'=>'max-width:200px;width:200px;' )),
            array('No Invoice',array('search'=>true,'sort'=>true)),
            array('Type',array('search'=>true,'sort'=>true,'select'=>config('jayon.deliverytype_selector') )),
            array('Merchant & Shop Name',array('search'=>true,'sort'=>true)),
            array('Box ID',array('search'=>true,'sort'=>true)),
            array('Status',array('search'=>true,'sort'=>true)),
            array('Directions',array('search'=>true,'sort'=>true)),
            array('Signatures',array('search'=>true,'sort'=>true)),
            array('Delivery Charge',array('search'=>true,'sort'=>true)),
            array('COD Surcharge',array('search'=>true,'sort'=>true)),
            array('COD Value',array('search'=>true,'sort'=>true)),
            array('Buyer',array('search'=>true,'sort'=>true)),
            array('ZIP',array('search'=>true,'sort'=>true)),
            array('Phone',array('search'=>true,'sort'=>true)),
            array('W x H x L = V',array('search'=>true,'sort'=>true)),
            array('Weight Range',array('search'=>true,'sort'=>true)),
        );

        //print $this->model->where('docFormat','picture')->get()->toJSON();

        $this->title = 'Device Zone Assignment';

        $this->crumb->addCrumb('Cost Report',url( strtolower($this->controller_name) ));

        //$this->additional_filter = View::make(strtolower($this->controller_name).'.addfilter')->with('submit_url','gl/static')->render();

        //$this->js_additional_param = "aoData.push( { 'name':'acc-period-to', 'value': $('#acc-period-to').val() }, { 'name':'acc-period-from', 'value': $('#acc-period-from').val() }, { 'name':'acc-code-from', 'value': $('#acc-code-from').val() }, { 'name':'acc-code-to', 'value': $('#acc-code-to').val() }, { 'name':'acc-company', 'value': $('#acc-company').val() } );";

        $this->product_info_url = strtolower($this->controller_name).'/info';

        $this->printlink = strtolower($this->controller_name).'/print';

        //table generator part

        $this->fields = array(
            array('ordertime',array('kind'=>'daterange', 'query'=>'like','pos'=>'both','show'=>true)),
            array('pickuptime',array('kind'=>'daterange', 'query'=>'like','pos'=>'both','show'=>true)),
            array('pickup_person',array('kind'=>'text', 'query'=>'like','pos'=>'both','show'=>true)),
            array('pickup_person',array('kind'=>'text', 'query'=>'like','pos'=>'both','show'=>true)),
            array('buyerdeliverytime',array('kind'=>'daterange','query'=>'like','pos'=>'both','show'=>true)),
            array('buyerdeliveryslot',array('kind'=>'text' , 'query'=>'like', 'pos'=>'both','show'=>true)),
            array('buyerdeliveryzone',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('buyerdeliverycity',array('kind'=>'text', 'query'=>'like','pos'=>'both','show'=>true)),
            array('shipping_address',array('kind'=>'text', 'query'=>'like','pos'=>'both','show'=>true)),
            array('merchant_trans_id',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('delivery_type',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array(config('jayon.jayon_members_table').'.merchantname',array('kind'=>'text','alias'=>'merchant_name','query'=>'like','callback'=>'merchantInfo','pos'=>'both','show'=>true)),
            array('delivery_id',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('status',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('directions',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('delivery_id',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('delivery_cost',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('cod_cost',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('total_price',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('buyer_name',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('shipping_zip',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('phone',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('volume',array('kind'=>'numeric','query'=>'like','pos'=>'both','show'=>true)),
            array('weight',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
        );

        $db = config('jayon.main_db');

        $this->def_order_by = 'ordertime';
        $this->def_order_dir = 'desc';
        $this->place_action = 'none';
        $this->show_select = false;

        $this->sql_key = 'delivery_id';
        $this->sql_table_name = config('jayon.incoming_delivery_table');
        $this->sql_connection = 'mysql';

        $this->responder_type = 's';

        return parent::printPage();
    }

    public function postShipmentlist()
    {
        $in = Request::input();

        $city = $in['city'];

        $date = $in['date'];

        //$pick_up_date = new MongoDate(strtotime($date));

        $pick_up_date = date('Y-m-d', strtotime($date));

        $shipments = Shipment::where('assignment_date','like', $pick_up_date.'%' )
                        ->where('status','=', config('jayon.trans_status_admin_dated'))
                        ->where('buyerdeliverycity','=',$city)->get();

        $shipments = $shipments->toArray();

        for($i = 0; $i < count($shipments); $i++){
            $shipments[$i]['assignmentdate'] = date('Y-m-d', strtotime($shipments[$i]['assignment_date']) );
            //$shipments[$i]['assignmentdate'] = date('Y-m-d', $shipments[$i]['assignmentdate']->sec );
        }

        $city = trim($city);
        /*
        $devices = Device::where('city','regex', new MongoRegex('/'.$city.'/i'))
                                ->where(function($on){
                                        $on->where('is_on','=',1)
                                            ->orWhere('is_on','=',strval(1));
                                })
                                ->get();
        */
        $devices = Device::where('city','like', '%'.$city.'%')
                                ->where(function($on){
                                        $on->where('is_on','=',1)
                                            ->orWhere('is_on','=',strval(1));
                                })
                                ->get();

        $caps = array();

        $dids = array();
        foreach($devices as $d){
            $dids[] = $d->id;
        }

        $qloads = Shipment::select(DB::raw('device_id, count(*) as cnt'))
                    ->where('assignment_date',$pick_up_date)
                    ->where('device_id','!=','')
                    ->groupBy('device_id')->get();

        $loads = array();

        foreach($qloads as $ld){
            $loads[$ld->device_id] = $ld['cnt'];
        }



        foreach($devices as $d){
            $caps[$d->key]['identifier'] = $d->identifier;
            $caps[$d->key]['id'] = $d->id;
            $caps[$d->key]['key'] = $d->key;
            $caps[$d->key]['city'] = $d->city;
            $caps[$d->key]['count'] = (isset( $loads[$d->id] ))?$loads[$d->id]:0;

        }

        return Response::json( array('result'=>'OK', 'shipment'=>$shipments, 'device'=>$caps ) );
        //print_r($caps);

    }

    public function postAssigndevice()
    {
        $in = Request::input();

        //better use device key to alleviate mysql dependency
        $device = Device::where('id','=', $in['device'] )->first();

        $shipments = Shipment::whereIn('delivery_id', $in['ship_ids'] )->get();

        //print_r($shipments->toArray());

        $ts = new MongoDate();

        foreach($shipments as $sh){

            $pre = clone $sh;


            //$sh->status = Config::get('jayon.trans_status_admin_zoned');
            $sh->status = config('jayon.trans_status_admin_devassigned');
            $sh->device_key = $device->key;
            $sh->device_name = $device->identifier;
            $sh->device_id = $device->id;
            $sh->save();


            $hdata = array();
            $hdata['historyTimestamp'] = $ts;
            $hdata['historyAction'] = 'assign_device';
            $hdata['historySequence'] = 1;
            $hdata['historyObjectType'] = 'shipment';
            $hdata['actor'] = Auth::user()->name;
            $hdata['actor_id'] = Auth::user()->_id;

            $hdata = array_merge($sh->toArray(), $hdata );

            History::insert($hdata);

            $sdata = array();
            $sdata['timestamp'] = $ts;
            $sdata['action'] = 'assign_device';
            $sdata['reason'] = 'initial';
            $sdata['objectType'] = 'shipment';
            $sdata['object'] = $sh->toArray();
            $sdata['preObject'] = $pre->toArray();
            $sdata['actor'] = Auth::user()->name;
            $sdata['actor_id'] = Auth::user()->_id;
            Shipmentlog::insert($sdata);

        }

        return Response::json( array('result'=>'OK', 'shipment'=>$shipments ) );

    }

    public function postDeviceavail()
    {

    }


    public function SQL_make_join($model)
    {
        //$model->with('coa');

        //PERIOD',TRANS_DATETIME,VCHR_NUM,ACC_DESCR,DESCRIPTN',TREFERENCE',CONV_CODE,AMOUNT',AMOUNT',DESCRIPTN'
        /*
        $model = $model->select('j10_a_salfldg.*','j10_acnt.DESCR as ACC_DESCR')
            ->leftJoin('j10_acnt', 'j10_a_salfldg.ACCNT_CODE', '=', 'j10_acnt.ACNT_CODE' );
            */
        return $model;
    }

    public function SQL_additional_query($model)
    {
        $in = Request::input();

        $period_from = Request::input('acc-period-from');
        $period_to = Request::input('acc-period-to');

        $db = config('lundin.main_db');

        $company = Request::input('acc-company');

        $company = strtolower($company);

        /*
        if($period_from == ''){
            $model = $model->select($company.'_a_salfldg.*',$company.'_acnt.DESCR as ACC_DESCR')
                ->leftJoin($company.'_acnt', $company.'_a_salfldg.ACCNT_CODE', '=', $company.'_acnt.ACNT_CODE' );
        }else{
            $model = $model->select($company.'_a_salfldg.*',$company.'_acnt.DESCR as ACC_DESCR')
                ->leftJoin($company.'_acnt', $company.'_a_salfldg.ACCNT_CODE', '=', $company.'_acnt.ACNT_CODE' )
                ->where('PERIOD','>=', Request::input('acc-period-from') )
                ->where('PERIOD','<=', Request::input('acc-period-to') )
                ->where('ACCNT_CODE','>=', Request::input('acc-code-from') )
                ->where('ACCNT_CODE','<=', Request::input('acc-code-to') )
                ->orderBy('PERIOD','DESC')
                ->orderBy('ACCNT_CODE','ASC')
                ->orderBy('TRANS_DATETIME','DESC');
        }
        */

        $txtab = config('jayon.incoming_delivery_table');

        $model = $model->select(
                DB::raw(
                    config('jayon.incoming_delivery_table').'.* ,'.
                    config('jayon.jayon_members_table').'.merchantname as merchant_name ,'.
                    config('jayon.applications_table').'.application_name as app_name ,'.
                    '('.$txtab.'.width * '.$txtab.'.height * '.$txtab.'.length ) as volume'
                )
            )
            ->leftJoin(config('jayon.jayon_members_table'), config('jayon.incoming_delivery_table').'.merchant_id', '=', config('jayon.jayon_members_table').'.id' )
            ->leftJoin(config('jayon.applications_table'), config('jayon.incoming_delivery_table').'.application_id', '=', config('jayon.applications_table').'.id' )
            ->where('status','=', config('jayon.trans_status_admin_dated') )
            ->orderBy('buyerdeliverycity','desc')
            ->orderBy('buyerdeliveryzone','desc');

        //print_r($in);


        //$model = $model->where('group_id', '=', 4);

        return $model;

    }

    public function SQL_before_paging($model)
    {
        /*
        $m_original_amount = clone($model);
        $m_base_amount = clone($model);

        $aux['total_data_base'] = $m_base_amount->sum('OTHER_AMT');
        $aux['total_data_converted'] = $m_original_amount->sum('AMOUNT');
        */
        //$this->aux_data = $aux;

        $aux = array();
        return $aux;
        //print_r($this->aux_data);

    }

    public function rows_post_process($rows, $aux = null){

        $date = '';
        $city = '';

        //print_r($rows);

        if(count($rows) > 0){

            for($i = 0; $i < count($rows); $i++){
                if($rows[$i][3] != $date){
                    $city = '';
                    $date = $rows[$i][3];
                    $rows[$i][3] = '<input type="radio" name="date_select" value="'.$rows[$i][3].'" class="date_select form-control" /> '.$rows[$i][3];
                }else{
                    $rows[$i][3] = '';
                }


                if($rows[$i][4] != $city){
                    $city = $rows[$i][4];
                    $rows[$i][4] = '<input type="radio" name="city_select" value="'.$rows[$i][4].'" class="city_select form-control" /> '.$rows[$i][4];
                }else{
                    $rows[$i][4] = '';
                }

            }


        }


        //print_r($this->aux_data);
        /*

        $total_base = 0;
        $total_converted = 0;
        $end = 0;

        $br = array_fill(0, $this->column_count(), '');


        $nrows = array();

        $subhead1 = '';
        $subhead2 = '';
        $subhead3 = '';

        $seq = 0;

        $subamount1 = 0;
        $subamount2 = 0;

        if(count($rows) > 0){

            for($i = 0; $i < count($rows);$i++){

                //print_r($rows[$i]['extra']);

                if($subhead1 == '' || $subhead1 != $rows[$i][1] || $subhead2 != $rows[$i][4] ){

                    $headline = $br;
                    if($subhead1 != $rows[$i][1]){
                        $headline[1] = '<b>'.$rows[$i]['extra']['PERIOD'].'</b>';
                    }else{
                        $headline[1] = '';
                    }

                    $headline[4] = '<b>'.$rows[$i]['extra']['ACCNT_CODE'].'</b>';
                    $headline['extra']['rowclass'] = 'row-underline';

                    if($subhead1 != ''){
                        $amtline = $br;
                        $amtline[8] = '<b>'.Ks::idr($subamount1).'</b>';
                        $amtline[10] = '<b>'.Ks::idr($subamount2).'</b>';
                        $amtline['extra']['rowclass'] = 'row-doubleunderline row-overline';

                        $nrows[] = $amtline;
                        $subamount1 = 0;
                        $subamount2 = 0;
                    }

                    $subamount1 += $rows[$i]['extra']['OTHER_AMT'];
                    $subamount2 += $rows[$i]['extra']['AMOUNT'];

                    $nrows[] = $headline;

                    $seq = 1;
                    $rows[$i][0] = $seq;

                    $rows[$i][8] = ($rows[$i]['extra']['CONV_CODE'] == 'IDR')?Ks::idr($rows[$i][8]):'';
                    $rows[$i][9] = ($rows[$i]['extra']['CONV_CODE'] == 'IDR')?Ks::dec2($rows[$i][9]):'';
                    $rows[$i][10] = Ks::usd($rows[$i][10]);

                    $nrows[] = $rows[$i];
                }else{
                    $seq++;
                    $rows[$i][0] = $seq;

                    $rows[$i][8] = ($rows[$i]['extra']['CONV_CODE'] == 'IDR')?Ks::idr($rows[$i][8]):'';
                    $rows[$i][9] = ($rows[$i]['extra']['CONV_CODE'] == 'IDR')?Ks::dec2($rows[$i][9]):'';
                    $rows[$i][10] = Ks::usd($rows[$i][10]);

                    $nrows[] = $rows[$i];


                }

                $total_base += doubleval( $rows[$i][8] );
                $total_converted += doubleval($rows[$i][10]);
                $end = $i;

                $subhead1 = $rows[$i][1];
                $subhead2 = $rows[$i][4];
            }

            // show total Page
            if($this->column_count() > 0){

                $tb = $br;
                $tb[1] = 'Total Page';
                $tb[8] = Ks::idr($total_base);
                $tb[10] = Ks::usd($total_converted);

                $nrows[] = $tb;

                if(!is_null($this->aux_data)){
                    $td = $br;
                    $td[1] = 'Total';
                    $td[8] = Ks::idr($aux['total_data_base']);
                    $td[10] = Ks::usd($aux['total_data_converted']);
                    $nrows[] = $td;
                }

            }

            return $nrows;

        }else{

            return $rows;

        }
        */

        // show total queried

        return $rows;

    }


    public function beforeSave($data)
    {

        if( isset($data['file_id']) && count($data['file_id'])){

            $mediaindex = 0;

            for($i = 0 ; $i < count($data['thumbnail_url']);$i++ ){

                $index = $mediaindex;

                $data['files'][ $data['file_id'][$i] ]['ns'] = $data['ns'][$i];
                $data['files'][ $data['file_id'][$i] ]['role'] = $data['role'][$i];
                $data['files'][ $data['file_id'][$i] ]['thumbnail_url'] = $data['thumbnail_url'][$i];
                $data['files'][ $data['file_id'][$i] ]['large_url'] = $data['large_url'][$i];
                $data['files'][ $data['file_id'][$i] ]['medium_url'] = $data['medium_url'][$i];
                $data['files'][ $data['file_id'][$i] ]['full_url'] = $data['full_url'][$i];
                $data['files'][ $data['file_id'][$i] ]['delete_type'] = $data['delete_type'][$i];
                $data['files'][ $data['file_id'][$i] ]['delete_url'] = $data['delete_url'][$i];
                $data['files'][ $data['file_id'][$i] ]['filename'] = $data['filename'][$i];
                $data['files'][ $data['file_id'][$i] ]['filesize'] = $data['filesize'][$i];
                $data['files'][ $data['file_id'][$i] ]['temp_dir'] = $data['temp_dir'][$i];
                $data['files'][ $data['file_id'][$i] ]['filetype'] = $data['filetype'][$i];
                $data['files'][ $data['file_id'][$i] ]['is_image'] = $data['is_image'][$i];
                $data['files'][ $data['file_id'][$i] ]['is_audio'] = $data['is_audio'][$i];
                $data['files'][ $data['file_id'][$i] ]['is_video'] = $data['is_video'][$i];
                $data['files'][ $data['file_id'][$i] ]['fileurl'] = $data['fileurl'][$i];
                $data['files'][ $data['file_id'][$i] ]['file_id'] = $data['file_id'][$i];
                $data['files'][ $data['file_id'][$i] ]['sequence'] = $mediaindex;

                $mediaindex++;

                $data['defaultpic'] = $data['file_id'][0];
                $data['defaultpictures'] = $data['files'][$data['file_id'][0]];

            }

        }else{

            $data['defaultpic'] = '';
            $data['defaultpictures'] = '';
        }

        $cats = Prefs::getShopCategory()->shopcatToSelection('slug', 'name', false);
        $data['shopcategory'] = $cats[$data['shopcategoryLink']];

            $data['shortcode'] = str_random(5);

        return $data;
    }

    public function beforeUpdate($id,$data)
    {

        if( isset($data['file_id']) && count($data['file_id'])){

            $mediaindex = 0;

            for($i = 0 ; $i < count($data['thumbnail_url']);$i++ ){

                $index = $mediaindex;

                $data['files'][ $data['file_id'][$i] ]['ns'] = $data['ns'][$i];
                $data['files'][ $data['file_id'][$i] ]['role'] = $data['role'][$i];
                $data['files'][ $data['file_id'][$i] ]['thumbnail_url'] = $data['thumbnail_url'][$i];
                $data['files'][ $data['file_id'][$i] ]['large_url'] = $data['large_url'][$i];
                $data['files'][ $data['file_id'][$i] ]['medium_url'] = $data['medium_url'][$i];
                $data['files'][ $data['file_id'][$i] ]['full_url'] = $data['full_url'][$i];
                $data['files'][ $data['file_id'][$i] ]['delete_type'] = $data['delete_type'][$i];
                $data['files'][ $data['file_id'][$i] ]['delete_url'] = $data['delete_url'][$i];
                $data['files'][ $data['file_id'][$i] ]['filename'] = $data['filename'][$i];
                $data['files'][ $data['file_id'][$i] ]['filesize'] = $data['filesize'][$i];
                $data['files'][ $data['file_id'][$i] ]['temp_dir'] = $data['temp_dir'][$i];
                $data['files'][ $data['file_id'][$i] ]['filetype'] = $data['filetype'][$i];
                $data['files'][ $data['file_id'][$i] ]['is_image'] = $data['is_image'][$i];
                $data['files'][ $data['file_id'][$i] ]['is_audio'] = $data['is_audio'][$i];
                $data['files'][ $data['file_id'][$i] ]['is_video'] = $data['is_video'][$i];
                $data['files'][ $data['file_id'][$i] ]['fileurl'] = $data['fileurl'][$i];
                $data['files'][ $data['file_id'][$i] ]['file_id'] = $data['file_id'][$i];
                $data['files'][ $data['file_id'][$i] ]['sequence'] = $mediaindex;

                $mediaindex++;

                $data['defaultpic'] = $data['file_id'][0];
                $data['defaultpictures'] = $data['files'][$data['file_id'][0]];

            }

        }else{

            $data['defaultpic'] = '';
            $data['defaultpictures'] = '';
        }

        if(!isset($data['shortcode']) || $data['shortcode'] == ''){
            $data['shortcode'] = str_random(5);
        }

        $cats = Prefs::getShopCategory()->shopcatToSelection('slug', 'name', false);
        $data['shopcategory'] = $cats[$data['shopcategoryLink']];


        return $data;
    }

    public function beforeUpdateForm($population)
    {
        //print_r($population);
        //exit();

        return $population;
    }

    public function afterSave($data)
    {

        $hdata = array();
        $hdata['historyTimestamp'] = new MongoDate();
        $hdata['historyAction'] = 'new';
        $hdata['historySequence'] = 0;
        $hdata['historyObjectType'] = 'asset';
        $hdata['historyObject'] = $data;
        History::insert($hdata);

        return $data;
    }

    public function afterUpdate($id,$data = null)
    {
        $data['_id'] = new MongoId($id);


        $hdata = array();
        $hdata['historyTimestamp'] = new MongoDate();
        $hdata['historyAction'] = 'update';
        $hdata['historySequence'] = 1;
        $hdata['historyObjectType'] = 'asset';
        $hdata['historyObject'] = $data;
        History::insert($hdata);


        return $id;
    }


    public function postAdd($data = null)
    {
        $this->validator = array(
            'shopDescription' => 'required'
        );

        return parent::postAdd($data);
    }

    public function postEdit($id,$data = null)
    {
        $this->validator = array(
            'shopDescription' => 'required'
        );

        //exit();

        return parent::postEdit($id,$data);
    }

    public function postDlxl()
    {

        $this->heads = null;

        $this->fields = array(
            array('ordertime',array('kind'=>'daterange', 'query'=>'like','pos'=>'both','show'=>true)),
            array('pickuptime',array('kind'=>'daterange', 'query'=>'like','pos'=>'both','show'=>true)),
            array('pickup_person',array('kind'=>'text', 'query'=>'like','pos'=>'both','show'=>true)),
            array('pickup_person',array('kind'=>'text', 'query'=>'like','pos'=>'both','show'=>true)),
            array('buyerdeliverytime',array('kind'=>'daterange','query'=>'like','pos'=>'both','show'=>true)),
            array('buyerdeliveryslot',array('kind'=>'text' , 'query'=>'like', 'pos'=>'both','show'=>true)),
            array('buyerdeliveryzone',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('buyerdeliverycity',array('kind'=>'text', 'query'=>'like','pos'=>'both','show'=>true)),
            array('shipping_address',array('kind'=>'text', 'query'=>'like','pos'=>'both','show'=>true)),
            array('merchant_trans_id',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('delivery_type',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('merchant_id',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('delivery_id',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('status',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('directions',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('delivery_id',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('delivery_cost',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('cod_cost',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('total_price',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('buyer_name',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('shipping_zip',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('phone',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('volume',array('kind'=>'numeric','query'=>'like','pos'=>'both','show'=>true)),
            array('actual_weight',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
            array('weight',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true))
        );

        $db = config('jayon.main_db');

        $this->def_order_by = 'ordertime';
        $this->def_order_dir = 'desc';
        $this->place_action = 'first';
        $this->show_select = true;

        $this->sql_key = 'delivery_id';
        $this->sql_table_name = config('jayon.incoming_delivery_table');
        $this->sql_connection = 'mysql';

        return parent::postSQLDlxl();
    }

    public function getImport(){

        $this->importkey = 'delivery_id';

        return parent::getImport();
    }

    public function postUploadimport()
    {
        $this->importkey = 'delivery_id';

        return parent::postUploadimport();
    }

    public function beforeImportCommit($data)
    {

        unset($data['createdDate']);
        unset($data['lastUpdate']);

        $data['created'] = $data['created_at'];

        unset($data['created_at']);
        unset($data['updated_at']);

        unset($data['volume']);
        unset($data['sessId']);
        unset($data['isHead']);

        return $data;
    }

    public function accountDesc($data)
    {

        return $data['ACCNT_CODE'];
    }

    public function extractCategory()
    {
        $category = Product::distinct('category')->get()->toArray();
        $cats = array(''=>'All');

        //print_r($category);
        foreach($category as $cat){
            $cats[$cat[0]] = $cat[0];
        }

        return $cats;
    }

    public function splitTag($data){
        $tags = explode(',',$data['tags']);
        if(is_array($tags) && count($tags) > 0 && $data['tags'] != ''){
            $ts = array();
            foreach($tags as $t){
                $ts[] = '<span class="tag">'.$t.'</span>';
            }

            return implode('', $ts);
        }else{
            return $data['tags'];
        }
    }

    public function splitShare($data){
        $tags = explode(',',$data['docShare']);
        if(is_array($tags) && count($tags) > 0 && $data['docShare'] != ''){
            $ts = array();
            foreach($tags as $t){
                $ts[] = '<span class="tag">'.$t.'</span>';
            }

            return implode('', $ts);
        }else{
            return $data['docShare'];
        }
    }

    public function locationName($data){
        if(isset($data['locationId']) && $data['locationId'] != ''){
            $loc = Assets::getLocationDetail($data['locationId']);
            return $loc->name;
        }else{
            return '';
        }

    }

    public function merchantInfo($data)
    {
        return $data['merchant_name'].'<hr />'.$data['app_name'];
    }

    public function catName($data)
    {
        return $data['shopcategory'];
    }

    public function rackName($data){
        if(isset($data['rackId']) && $data['rackId'] != ''){
            $loc = Assets::getRackDetail($data['rackId']);
            if($loc){
                return $loc->SKU;
            }else{
                return '';
            }
        }else{
            return '';
        }

    }

    public function postSynclegacy(){

        set_time_limit(0);

        $mymerchant = Merchant::where('group_id',4)->get();

        $count = 0;

        foreach($mymerchant->toArray() as $m){

            $member = Member::where('legacyId',$m['id'])->first();

            if($member){

            }else{
                $member = new Member();
            }

            foreach ($m as $k=>$v) {
                $member->{$k} = $v;
            }

            if(!isset($member->status)){
                $member->status = 'inactive';
            }

            if(!isset($member->url)){
                $member->url = '';
            }

            $member->legacyId = new MongoInt32($m['id']);

            $member->roleId = Prefs::getRoleId('Merchant');

            $member->unset('id');

            $member->save();

            $count++;
        }

        return Response::json( array('result'=>'OK', 'count'=>$count ) );

    }

    public function statNumbers($data){
        $datemonth = date('M Y',time());
        $firstday = Carbon::parse('first day of '.$datemonth);
        $lastday = Carbon::parse('last day of '.$datemonth)->addHours(23)->addMinutes(59)->addSeconds(59);

        $qval = array('$gte'=>new MongoDate(strtotime($firstday->toDateTimeString())),'$lte'=>new MongoDate( strtotime($lastday->toDateTimeString()) ));

        $qc = array();

        $qc['adId'] = $data['_id'];

        $qc['clickedAt'] = $qval;

        $qv = array();

        $qv['adId'] = $data['_id'];

        $qv['viewedAt'] = $qval;

        $clicks = Clicklog::whereRaw($qc)->count();

        $views = Viewlog::whereRaw($qv)->count();

        return $clicks.' clicks<br />'.$views.' views';
    }

    public function namePic($data)
    {
        $name = HTML::link('property/view/'.$data['_id'],$data['address']);

        $thumbnail_url = '';

        $ps = config('picture.sizes');


        if(isset($data['files']) && count($data['files'])){
            $glinks = '';

            $gdata = $data['files'][$data['defaultpic']];

            $thumbnail_url = $gdata['thumbnail_url'];
            foreach($data['files'] as $g){
                $g['caption'] = ( isset($g['caption']) && $g['caption'] != '')?$g['caption']:$data['SKU'];
                $g['full_url'] = isset($g['full_url'])?$g['full_url']:$g['fileurl'];
                foreach($ps as $k=>$s){
                    if(isset($g[$k.'_url'])){
                        $glinks .= '<input type="hidden" class="g_'.$data['_id'].'" data-caption="'.$k.'" value="'.$g[$k.'_url'].'" />';
                    }
                }
            }
            if(isset($data['useImage']) && $data['useImage'] == 'linked'){
                $thumbnail_url = $data['extImageURL'];
                $display = HTML::image($thumbnail_url.'?'.time(), $thumbnail_url, array('class'=>'thumbnail img-polaroid','style'=>'cursor:pointer;','id' => $data['_id'])).$glinks;
            }else{
                $display = HTML::image($thumbnail_url.'?'.time(), $thumbnail_url, array('class'=>'thumbnail img-polaroid','style'=>'cursor:pointer;','id' => $data['_id'])).$glinks;
            }
            return $display;
        }else{
            return $data['SKU'];
        }
    }

    public function weightRange($data)
    {
        return Prefs::getWeightRange($data['weight'],$data['application_id']);
    }

    public function showWHL($data)
    {
        return $data['width'].'x'.$data['height'].'x'.$data['length'];
    }

    public function sameEmail($data)
    {
        if($data['same_email'] == 1){
            return '<span class="dupe">'.$data['email'].'</span>';
        }else{
            return $data['email'];
        }
    }

    public function phoneList($data)
    {
        $phones = array($data['phone'],$data['mobile1'],$data['mobile2']);
        $phones = array_filter($phones);
        $phones = implode('<br />', $phones);

        if($data['same_phone'] == 1){
            return '<span class="dupe">'.$phones.'</span>';
        }else{
            return $phones;
        }
    }

    public function dispBar($data)

    {
        $display = HTML::image(url('qr/'.urlencode(base64_encode($data['delivery_id'].'|'.$data['merchant_trans_id'] ))), $data['merchant_trans_id'], array('id' => $data['delivery_id'], 'style'=>'width:100px;height:auto;' ));
        //$display = '<a href="'.url('barcode/dl/'.urlencode($data['SKU'])).'">'.$display.'</a>';
        return $display.'<br />'. '<a href="'.url('asset/detail/'.$data['delivery_id']).'" >'.$data['merchant_trans_id'].'</a>';
    }

    public function statusList($data)
    {

        return '<span class="orange white-text">'.$data['status'].'</span><br /><span class="brown">'.$data['pickup_status'].'</span><br /><span class="green">'.$data['courier_status'].'</span><br /><span class="maroon">'.$data['warehouse_status'].'</span>';
    }


    public function colorizetype($data)
    {
        return Prefs::colorizetype($data['delivery_type']);
    }


    public function pics($data)
    {
        $name = HTML::link('products/view/'.$data['_id'],$data['productName']);
        if(isset($data['thumbnail_url']) && count($data['thumbnail_url'])){
            $display = HTML::image($data['thumbnail_url'][0].'?'.time(), $data['filename'][0], array('style'=>'min-width:100px;','id' => $data['_id']));
            return $display.'<br /><span class="img-more" id="'.$data['_id'].'">more images</span>';
        }else{
            return $name;
        }
    }

    public function getPrintlabel($sessionname, $printparam, $format = 'html' )
    {
        $pr = explode(':',$printparam);

        $columns = $pr[0];
        $resolution = $pr[1];
        $cell_width = $pr[2];
        $cell_height = $pr[3];
        $margin_right = $pr[4];
        $margin_bottom = $pr[5];
        $font_size = $pr[6];
        $code_type = $pr[7];
        $left_offset = $pr[8];
        $top_offset = $pr[9];

        $session = Printsession::find($sessionname)->toArray();
        $labels = Asset::whereIn('_id', $session)->get()->toArray();

        $skus = array();
        foreach($labels as $l){
            $skus[] = $l['SKU'];
        }

        $skus = array_unique($skus);

        $products = Asset::whereIn('SKU',$skus)->get()->toArray();

        $plist = array();
        foreach($products as $product){
            $plist[$product['SKU']] = $product;
        }

        return View::make('asset.printlabel')
            ->with('columns',$columns)
            ->with('resolution',$resolution)
            ->with('cell_width',$cell_width)
            ->with('cell_height',$cell_height)
            ->with('margin_right',$margin_right)
            ->with('margin_bottom',$margin_bottom)
            ->with('font_size',$font_size)
            ->with('code_type',$code_type)
            ->with('left_offset', $left_offset)
            ->with('top_offset', $top_offset)
            ->with('products',$plist)
            ->with('labels', $labels);
    }


    public function getViewpics($id)
    {

    }

    public function updateStock($data){

        //print_r($data);

        $outlets = $data['outlets'];
        $outletNames = $data['outletNames'];
        $addQty = $data['addQty'];
        $adjustQty = $data['adjustQty'];

        unset($data['outlets']);
        unset($data['outletNames']);
        unset($data['addQty']);
        unset($data['adjustQty']);

        for( $i = 0; $i < count($outlets); $i++)
        {

            $su = array(
                    'outletId'=>$outlets[$i],
                    'outletName'=>$outletNames[$i],
                    'productId'=>$data['id'],
                    'SKU'=>$data['SKU'],
                    'productDetail'=>$data,
                    'status'=>'available',
                    'createdDate'=>new MongoDate(),
                    'lastUpdate'=>new MongoDate()
                );

            if($addQty[$i] > 0){
                for($a = 0; $a < $addQty[$i]; $a++){
                    $su['_id'] = str_random(40);
                    Stockunit::insert($su);
                }
            }

            if($adjustQty[$i] > 0){
                $td = Stockunit::where('outletId',$outlets[$i])
                    ->where('productId',$data['id'])
                    ->where('SKU', $data['SKU'])
                    ->where('status','available')
                    ->orderBy('createdDate', 'asc')
                    ->take($adjustQty[$i])
                    ->get();

                foreach($td as $d){
                    $d->status = 'deleted';
                    $d->lastUpdate = new MongoDate();
                    $d->save();
                }
            }
        }


    }

}
