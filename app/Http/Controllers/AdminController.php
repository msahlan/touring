<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use Former\Facades\Former;
use App\Helpers\Logger;
use App\Helpers\Wupload;
use App\Models\Importsession;


use Creitive\Breadcrumbs\Breadcrumbs;

use Auth;
use Event;
use View;
use Input;
use Request;
use Response;
use Mongomodel;
use \MongoRegex;
use \MongoDate;
use \MongoId;
use \MongoInt32;
use DB;
use HTML;
use Excel;
use Validator;
use Route;

class AdminController extends Controller {

	public $crumb;

	public $model;

	public $heads = array();

	public $fields = array();

	public $collection;

	public $controller_name;

	public $form;

	public $form_framework = 'TwitterBootstrap3';

	public $form_class = 'form-horizontal';

	public $validator = array();

	public $actions = '';

	public $form_add = 'new';

	public $form_edit = 'edit';

	public $view_object = 'view';

	public $title = '';

    public $ajaxsource = null;

    public $addurl = null;

    public $importurl = null;

    public $importkey = null;

    public $rowdetail = null;

    public $delurl = null;

    public $toggleurl = null;

    public $dlxl = null;

    public $newbutton = null;

    public $backlink = '';

    public $printlink = '';

    public $pdflink = '';

    public $xlslink = '';

    public $makeActions = 'makeActions';

    public $can_add = true;

    public $can_import = true;

    public $can_export = true;

    public $can_download = true;

    public $is_report = false;

    public $report_action = '';

    public $is_additional_action = false;

    public $additional_action = '';

    public $additional_filter = '';

    public $js_additional_param = '';

    public $table_raw = '';

    public $table_dnd = false;

    public $table_dnd_url = '';

    public $table_dnd_idx = 0;

    public $table_group = false;

    public $table_group_field = '';

    public $table_group_idx = 0;

    public $table_group_collapsible = false;

    public $additional_query = false;

    public $modal_sets = '';

    public $js_table_event = '';

    public $def_order_by = 'lastUpdate';

    public $def_order_dir = 'desc';

    public $place_action = 'both'; // first, both

    public $show_select = true; // first, both

    public $additional_page_data = array();

    public $table_view = 'tables.material';

    public $report_view = 'tables.report';

    public $report_data = null;

    public $report_file_path = null;

    public $report_file_name = null;

    public $report_type = false;

    public $report_entity = false;

    public $doc_number = false;

    public $additional_table_param = array();
    //public $product_info_url = 'ajax/productinfo';

    public $sql_connection = '';

    public $sql_table_name = '';

    public $sql_key = 'id';

    public $product_info_url = null;

    public $prefix = null;

    public $no_paging = false;

    public $aux_data = null;

    public $column_styles = '';

    public $responder_type = 's';

    public $print = false;

    public $pdf = false;

    public $xls = false;

    public $import_main_form = 'shared.importinput';

    public $import_aux_form = '';

    public $export_output_fields = null;

    public $report_filter_input = null;

    public $import_validate_list = null;

    public $import_commit_submit = null;

    public $import_update_exclusion = array();

    public $import_commit_url = null;

    public $search_fields = null;

	public function __construct(){

		date_default_timezone_set('Asia/Jakarta');

		Former::framework($this->form_framework);

        $this->middleware('auth');

        //$this->beforeFilter('jauth', array('on'=>'get', 'only'=>array('getIndex','getAdd','getEdit') ));

        $this->backlink = strtolower($this->controller_name);

        $this->crumb = new \Creitive\Breadcrumbs\Breadcrumbs;

        $this->crumb->setDivider('');
        $this->crumb->setCssClasses('breadcrumb');
        $this->crumb->addCrumb('Home',url('/'));

        Logger::access();

        //print $_ENV['LARAVEL_ENV'];

	}


	/**
	 * Setup the layout used by the controller.
	 *
	 * @return void
	 */
	protected function setupLayout()
	{
		if ( ! is_null($this->layout))
		{
			$this->layout = View::make($this->layout);
		}
	}

    public function getIndex()
    {

        $this->crumb->addCrumb($this->title,url('/'));

        $controller_name = strtolower($this->controller_name);

        $actor = (isset(Auth::user()->email))?Auth::user()->name.' - '.Auth::user()->email:'guest';
        Event::fire('log.a',array($controller_name, 'view list' ,$actor,'OK'));

        //$this->can_add = false;

        return $this->pageGenerator();
    }

    public function getPrintfile($_id)
    {
        $file = Document::find($_id);
        if($file){
            $body = file_get_contents($file->fullpath);
            $controller_name = strtolower($this->controller_name);
            $actor = (isset(Auth::user()->email))?Auth::user()->name.' - '.Auth::user()->email:'guest';
            Event::fire('log.a',array($controller_name, 'print file content' ,$actor,'OK'));
            print $body;
        }else{
            return $this->get_not_found_page();
        }

    }

    public function getPdffile($_id)
    {

        $file = Document::find($_id);
        if($file){
            $body = file_get_contents($file->fullpath);
            $controller_name = strtolower($this->controller_name);
            $actor = (isset(Auth::user()->email))?Auth::user()->name.' - '.Auth::user()->email:'guest';
            Event::fire('log.a',array($controller_name, 'print file content' ,$actor,'OK'));

            return PDF::loadHTML($body)->setPaper('a4')
                     ->setOrientation('landscape')
                     ->setOption('margin-bottom', 0)
                     ->stream( str_replace('html', 'pdf', $file->filename ) );

        }else{
            return $this->get_not_found_page();
        }


        //return $this->printPage();
    }


    public function getPrint()
    {

        $actor = (isset(Auth::user()->email))?Auth::user()->name.' - '.Auth::user()->email:'guest';
        Event::fire('log.a',array($controller_name, 'print page' ,$actor,'OK'));

        return $this->printPage();
    }

    public function postIndex()
    {

        return $this->tableResponder();
    }

    public function postSQLIndex()
    {
        return $this->SQLtableResponder();
    }

    public function printGenerator()
    {


        $this->crumb->addCrumb($this->title,url('/'));

        $controller_name = strtolower($this->controller_name);

        $this->print = true;
        $this->no_paging = true;

        if($this->responder_type == 's'){
            $table = $this->SQLtableResponder();
        }else{
            $table = $this->tableResponder();
        }

        $actor = (isset(Auth::user()->email))?Auth::user()->name.' - '.Auth::user()->email:'guest';
        Event::fire('log.a',array($controller_name, 'view static list' ,$actor,'OK'));


        $this->table_view = 'print.table';

        $this->table_raw = $table;

        //print_r($table);

        return $this->pageGenerator();
    }

    public function printPage()
    {


        $controller_name = strtolower($this->controller_name);

        $this->print = true;
        $this->no_paging = true;

        if($this->responder_type == 's'){
            $table = $this->SQLtableResponder();
        }else{
            $table = $this->tableResponder();
        }

        $actor = (isset(Auth::user()->email))?Auth::user()->name.' - '.Auth::user()->email:'guest';
        Event::fire('log.a',array($controller_name, 'view static list' ,$actor,'OK'));


        $this->table_view = 'print.plain';

        $this->table_raw = $table;

        //print_r($table);

        return $this->pageGenerator();
    }

    public function printThrough($_id)
    {

        $doc = Document::find($_id);


        $actor = (isset(Auth::user()->email))?Auth::user()->name.' - '.Auth::user()->email:'guest';
        Event::fire('log.a',array($controller_name, 'print through document' ,$actor,'OK'));

        if($doc){
            $content = file_get_contents($doc->fullpath);
            print $content;
        }else{
            return View::make('shared.notfound');
        }
    }

    public function printReport()
    {

        $controller_name = strtolower($this->controller_name);

        $actor = (isset(Auth::user()->email))?Auth::user()->name.' - '.Auth::user()->email:'guest';
        Event::fire('log.a',array($controller_name, 'view static list' ,$actor,'OK'));

        $this->report_view = 'print.report';

        return $this->reportPageGenerator();
    }

	public function pageGenerator(){

		//$action_selection = Former::select( config('kickstart.actionselection'))->name('action');

		$heads = $this->heads;
        $fields = $this->fields;

        $route = Route::current();
        $ajaxsource = $route->getPrefix().'/'.strtolower($this->controller_name);

        $this->ajaxsource = (is_null($this->ajaxsource))? $ajaxsource : $this->ajaxsource;

        $this->addurl = (is_null($this->addurl))? $route->getPrefix().'/'.strtolower($this->controller_name).'/add': $this->addurl;

        $this->importurl = (is_null($this->importurl))? $route->getPrefix().'/'.strtolower($this->controller_name).'/import': $this->importurl;

        $this->rowdetail = (is_null($this->rowdetail))? $route->getPrefix().'/'.strtolower($this->controller_name).'.rowdetail': $this->rowdetail;

        $this->delurl = (is_null($this->delurl))? $route->getPrefix().'/'.strtolower($this->controller_name).'/del': $this->delurl;

        $this->toggleurl = (is_null($this->toggleurl))? $route->getPrefix().'/'.strtolower($this->controller_name).'/toggle': $this->toggleurl;

        $this->newbutton = (is_null($this->newbutton))? $route->getPrefix().'/'.str_singular($this->controller_name): $this->newbutton;

        //dialog related url
        //$this->product_info_url = (is_null($this->product_info_url))? strtolower($this->controller_name).'/info': $this->product_info_url;

        $this->prefix = (is_null($this->prefix))? strtolower($this->controller_name):$this->prefix;

		$select_all = Former::checkbox()->name('All')->check(false)->id('select_all');

		// add selector and sequence columns
        $start_index = -1;
        if($this->place_action == 'both' || $this->place_action == 'first'){
            array_unshift($heads, array('Actions',array('sort'=>false,'clear'=>true,'class'=>'action')));
            array_unshift($fields, array('',array('sort'=>false,'clear'=>true,'class'=>'action')));
        }
        if($this->show_select == true){
            array_unshift($heads, array($select_all,array('sort'=>false)));
            array_unshift($fields, array('',array('sort'=>false)));
        }else{
            $start_index = $start_index + 1;
        }
		array_unshift($heads, array('#',array('sort'=>false)));

        array_unshift($fields, array('',array('sort'=>false)));

		// add action column
        if($this->place_action == 'both'){
            array_push($heads,
                array('Actions',array('search'=>false,'sort'=>false,'clear'=>true,'class'=>'action'))
            );
            array_push($fields,
                array('',array('search'=>false,'sort'=>false,'clear'=>true,'class'=>'action'))
            );
        }

		$disablesort = array();

		for($s = 0; $s < count($heads);$s++){
			if($heads[$s][1]['sort'] == false){
				$disablesort[] = $s;
			}
		}

		$disablesort = implode(',',$disablesort);

        /* additional features */

        $this->dlxl = (is_null($this->dlxl))? $route->getPrefix().'/'.strtolower($this->controller_name).'/dlxl': $this->dlxl;


        $this->printlink = (is_null($this->printlink) || $this->printlink == '')? $route->getPrefix().'/'.strtolower($this->controller_name).'/static': $this->printlink;

        //print_r($this->crumb);

		return view($this->table_view)
			->with('title',$this->title )
			->with('newbutton', $this->newbutton )
			->with('disablesort',$disablesort )
			->with('addurl',$this->addurl )
            ->with('importurl',$this->importurl )
			->with('ajaxsource',url($this->ajaxsource) )
            ->with('ajaxtoggle',url($this->toggleurl))
			->with('ajaxdel',url($this->delurl) )
            ->with('ajaxdlxl',url($this->dlxl) )
			->with('crumb',$this->crumb )
            ->with('printlink', $this->printlink )
            ->with('can_add', $this->can_add )
            ->with('can_import', $this->can_import )
            ->with('can_export', $this->can_export )
            ->with('can_download', $this->can_download )
            ->with('is_report',$this->is_report)
            ->with('report_action',$this->report_action)
            ->with('is_additional_action',$this->is_additional_action)
            ->with('additional_action',$this->additional_action)
            ->with('additional_filter',$this->additional_filter)
            ->with('js_additional_param', $this->js_additional_param)
            ->with('modal_sets', $this->modal_sets)
            ->with('table',$this->table_raw)
            ->with('table_dnd', $this->table_dnd)
            ->with('table_dnd_url', $this->table_dnd_url)
            ->with('table_dnd_idx', $this->table_dnd_idx)
            ->with('table_group', $this->table_group)
            ->with('table_group_field', $this->table_group_field)
            ->with('table_group_idx', $this->table_group_idx)
            ->with('table_group_collapsible', $this->table_group_collapsible)
            ->with('js_table_event', $this->js_table_event)
            ->with('column_styles', $this->column_styles)
            ->with('additional_page_data',$this->additional_page_data)
            ->with('additional_table_param',$this->additional_table_param)
            ->with('product_info_url',$this->product_info_url)
            ->with('prefix',$this->prefix)
			->with('heads',$heads )
            ->with('fields',$fields)
            ->with('start_index',$start_index)
			->with('row',$this->rowdetail );


	}

    public function reportPageGenerator(){

        //$action_selection = Former::select( config('kickstart.actionselection'))->name('action');

        $heads = $this->heads;
        $fields = $this->fields;

        $this->ajaxsource = (is_null($this->ajaxsource))? strtolower($this->controller_name): $this->ajaxsource;

        $this->addurl = (is_null($this->addurl))? strtolower($this->controller_name).'/add': $this->addurl;

        $this->importurl = (is_null($this->importurl))? strtolower($this->controller_name).'/import': $this->importurl;

        $this->rowdetail = (is_null($this->rowdetail))? strtolower($this->controller_name).'.rowdetail': $this->rowdetail;

        $this->delurl = (is_null($this->delurl))? strtolower($this->controller_name).'/del': $this->delurl;

        $this->newbutton = (is_null($this->newbutton))? str_singular($this->controller_name): $this->newbutton;

        //dialog related url
        //$this->product_info_url = (is_null($this->product_info_url))? strtolower($this->controller_name).'/info': $this->product_info_url;

        $this->prefix = (is_null($this->prefix))? strtolower($this->controller_name):$this->prefix;

        $select_all = Former::checkbox()->name('All')->check(false)->id('select_all');

        // add selector and sequence columns
        $start_index = -1;
        if($this->place_action == 'both' || $this->place_action == 'first'){
            array_unshift($heads, array('Actions',array('sort'=>false,'clear'=>true,'class'=>'action')));
            array_unshift($fields, array('',array('sort'=>false,'clear'=>true,'class'=>'action')));
        }
        if($this->show_select == true){
            array_unshift($heads, array($select_all,array('sort'=>false)));
            array_unshift($fields, array('',array('sort'=>false)));
        }else{
            $start_index = $start_index + 1;
        }
        array_unshift($heads, array('#',array('sort'=>false)));

        array_unshift($fields, array('',array('sort'=>false)));

        // add action column
        if($this->place_action == 'both'){
            array_push($heads,
                array('Actions',array('search'=>false,'sort'=>false,'clear'=>true,'class'=>'action'))
            );
            array_push($fields,
                array('',array('search'=>false,'sort'=>false,'clear'=>true,'class'=>'action'))
            );
        }

        $disablesort = array();

        for($s = 0; $s < count($heads);$s++){
            if($heads[$s][1]['sort'] == false){
                $disablesort[] = $s;
            }
        }

        $disablesort = implode(',',$disablesort);

        /* additional features */

        $this->dlxl = (is_null($this->dlxl))? strtolower($this->controller_name).'/dlxl': $this->dlxl;


        $this->printlink = (is_null($this->printlink) || $this->printlink == '')? strtolower($this->controller_name).'/print': $this->printlink;

        $this->pdflink = (is_null($this->pdflink) || $this->pdflink == '')? strtolower($this->controller_name).'/genpdf': $this->pdflink;

        $this->xlslink = (is_null($this->xlslink) || $this->xlslink == '')? strtolower($this->controller_name).'/genxls': $this->xlslink;
        /*
        if($this->report_entity == false){

        }else{
            $this->report_entity = (is_null($this->report_entity) || $this->report_entity == '')? strtolower($this->controller_name): $this->report_entity;

            if($this->doc_number == false){
                $sequencer = new Sequence();
                $this->doc_number = $sequencer->getNewId($this->report_entity);
            }
        }
        */

            $html = View::make($this->report_view)
                ->with('title',$this->title )
                ->with('report_data', $this->report_data)
                ->with('newbutton', $this->newbutton )
                ->with('disablesort',$disablesort )
                ->with('addurl',$this->addurl )
                ->with('importurl',$this->importurl )
                ->with('ajaxsource',url($this->ajaxsource) )
                ->with('ajaxdel',url($this->delurl) )
                ->with('ajaxdlxl',url($this->dlxl) )
                ->with('crumb',$this->crumb )
                ->with('printlink', $this->printlink )
                ->with('pdflink', $this->pdflink )
                ->with('xlslink', $this->xlslink )
                ->with('can_add', $this->can_add )
                ->with('is_report',$this->is_report)
                ->with('report_action',$this->report_action)
                ->with('doc_number',$this->doc_number)
                ->with('is_additional_action',$this->is_additional_action)
                ->with('additional_action',$this->additional_action)
                ->with('additional_filter',$this->additional_filter)
                ->with('js_additional_param', $this->js_additional_param)
                ->with('modal_sets', $this->modal_sets)
                ->with('tables',$this->table_raw)
                ->with('table_dnd', $this->table_dnd)
                ->with('table_dnd_url', $this->table_dnd_url)
                ->with('table_dnd_idx', $this->table_dnd_idx)
                ->with('table_group', $this->table_group)
                ->with('table_group_field', $this->table_group_field)
                ->with('table_group_idx', $this->table_group_idx)
                ->with('table_group_collapsible', $this->table_group_collapsible)
                ->with('js_table_event', $this->js_table_event)
                ->with('column_styles', $this->column_styles)
                ->with('additional_page_data',$this->additional_page_data)
                ->with('additional_table_param',$this->additional_table_param)
                ->with('product_info_url',$this->product_info_url)
                ->with('prefix',$this->prefix)
                ->with('heads',$heads )
                ->with('fields',$fields)
                ->with('start_index',$start_index)
                ->with('row',$this->rowdetail )
                ->with('pdf',$this->pdf);

        /*
        PDF::loadHTML($html->render())->setPaper('a4')
                 ->setOrientation('landscape')
                 ->setOption('margin-bottom', 0)
                 ->save($this->report_file_path.$this->report_file_name);
        */
        if($this->report_file_name){


            file_put_contents($this->report_file_path.$this->report_file_name, $html);

            $sd = new Document();
            $sd->timestamp = new MongoDate();
            $sd->type = $this->report_type;
            $sd->fullpath = $this->report_file_path.$this->report_file_name;
            $sd->filename = $this->report_file_name;
            $sd->creator_id = Auth::user()->_id;
            $sd->creator_name = Auth::user()->name;
            $sd->save();

        }


        if($this->pdf == true){

            $html->render();

            $snappy = App::make('snappy.pdf');

            return PDF::loadHTML($html)->setPaper('a4')
                     ->setOrientation('landscape')->setOption('margin-bottom', 0)->stream($this->report_file_name);

        }

        if($this->xls == true){

            $tables = $this->table_raw;

            $heads = $this->additional_filter;

            Excel::create($this->report_file_name, function($excel) use($tables, $heads){

                $excel->sheet('New sheet', function($sheet) use($tables, $heads){

                    $xls_view = 'tables.xls';

                    $sheet->loadView($xls_view)
                        ->with('heads',$heads )
                        ->with('tables',$tables);

                });

            })->download('xls');

        }else{

            return $html;

        }

    }


	public function tableResponder()
	{
        set_time_limit(0);
        date_default_timezone_set('Asia/Jakarta');

		$fields = $this->fields;

        $count_all = 0;
        $count_display_all = 0;

		//print_r($fields);

		//array_unshift($fields, array('select',array('kind'=>false)));
		array_unshift($fields, array('seq',array('kind'=>false)));
        if($this->place_action == 'both' || $this->place_action == 'first'){
            array_unshift($fields, array('action',array('kind'=>false)));
        }

		$pagestart = Request::input('iDisplayStart');
		$pagelength = Request::input('iDisplayLength');

		$limit = array($pagelength, $pagestart);

        $defsort = 1;
        $defdir = -1;

        $idx = 0;
        $q = array();

        $hilite = array();
        $hilite_replace = array();


        //$model = $this->model;

        //$table = $emodel->getTable();

        $model = $this->model;

        $count_all = $model->count();

        $model = $this->SQL_additional_query($model);

        //$model = $this->SQL_make_join($model);

        //$comres = $this->SQLcompileSearch($fields, $model);

        $comres = $this->MongoCompileSearch($fields, $model);

        $model = $comres['model'];
        $q = $comres['q'];
		//print_r($q);


		/* first column is always sequence number, so must be omitted */

		$fidx = Request::input('iSortCol_0') - 1;

		$fidx = ($fidx == -1 )?0:$fidx;

        if(Request::input('iSortCol_0') == 0){
            $sort_col = $this->def_order_by;

            $sort_dir = $this->def_order_dir;
        }else{
            $sort_col = $fields[$fidx][0];

            $sort_dir = Request::input('sSortDir_0');

        }


		/*
		if(count($q) > 0){
			$results = $model->skip( $pagestart )->take( $pagelength )->orderBy($sort_col, $sort_dir )->get();
			$count_display_all = $model->count();
		}else{
			$results = $model->find(array(),array(),array($sort_col=>$sort_dir),$limit);
			$count_display_all = $model->count();
		}
		*/

        //$model->where('docFormat','picture');



        $count_display_all = $model->count();

        $this->aux_data = $this->SQL_before_paging($model);

        if($this->no_paging == true){
            $results = $model->orderBy($sort_col, $sort_dir )->timeout(-1)->get();
        }else{
            $results = $model->skip( $pagestart )->take( $pagelength )->orderBy($sort_col, $sort_dir )->timeout(-1)->get();
        }

        //print_r($results->toArray());

		$aadata = array();

		$form = $this->form;

		$counter = 1 + $pagestart;


        //$count_display_all = count($results);


		foreach ($results as $doc) {

			$extra = $doc;

			//$select = Former::checkbox('sel_'.$doc['_id'])->check(false)->id($doc['_id'])->class('selector');
            $actionMaker = $this->makeActions;

			$actions = $this->$actionMaker($doc);

			$row = array();

			$row[] = $counter;

            if($this->show_select == true){
                //$sel = Former::checkbox('sel_'.$doc['_id'])->check(false)->label(false)->id($doc['_id'])->class('selector')->__toString();
                $sel = '<input type="checkbox" name="sel_'.$doc['_id'].'" id="'.$doc['_id'].'" value="'.$doc['_id'].'" class="selector" />';
                $row[] = $sel;
            }

            if($this->place_action == 'both' || $this->place_action == 'first'){
                $row[] = $actions;
            }


			foreach($fields as $field){
				if($field[1]['kind'] != false && $field[1]['show'] == true){

					$fieldarray = explode('.',$field[0]);
					if(is_array($fieldarray) && count($fieldarray) > 1){
						$fieldarray = implode('\'][\'',$fieldarray);
						$cstring = '$label = (isset($doc[\''.$fieldarray.'\']))?true:false;';
						eval($cstring);
					}else{
						$label = (isset($doc[$field[0]]))?true:false;
					}


					if($label){

						if( isset($field[1]['callback']) && $field[1]['callback'] != ''){
							$callback = $field[1]['callback'];
							$row[] = $this->$callback($doc, $field[0]);
						}else{
							if($field[1]['kind'] == 'datetime' || $field[1]['kind'] == 'datetimerange'){
                                if($doc[$field[0]] != ''){
                                    //print_r($doc[$field[0]]);
                                    if($doc[$field[0]] instanceof MongoDate){
                                        $rowitem = date('d-m-Y H:i:s',$doc[$field[0]]->sec);
                                    }elseif ($doc[$field[0]] instanceof Date) {
                                        $rowitem = date('d-m-Y H:i:s',$doc[$field[0]]);
                                    }else{
                                        //$rowitem = $doc[$field[0]];
                                        if(is_array($doc[$field[0]])){
                                            $rowitem = date('d-m-Y H:i:s', time() );
                                        }else{
                                            $rowitem = date('d-m-Y H:i:s',strtotime($doc[$field[0]]) );
                                        }
                                    }
                                }
							}elseif($field[1]['kind'] == 'date' || $field[1]['kind'] == 'daterange'){
                                if($doc[$field[0]] != ''){
                                    //print_r($doc[$field[0]]);
                                    if($doc[$field[0]] instanceof MongoDate){
                                        $rowitem = date('d-m-Y',$doc[$field[0]]->sec);
                                    }elseif ($doc[$field[0]] instanceof Date) {
                                        $rowitem = date('d-m-Y',$doc[$field[0]]);
                                    }else{
                                        if(is_array($doc[$field[0]])){
                                            if(isset( $doc[$field[0]]['sec'])){
                                                $rowitem = date('d-m-Y',$doc[$field[0]]['sec'] );
                                            }
                                        }else{
                                            $rowitem = date('d-m-Y',strtotime($doc[$field[0]]) );
                                        }
                                    }
                                }
							}elseif($field[1]['kind'] == 'currency'){
								$num = (double) $doc[$field[0]];
								$rowitem = number_format($num,2,',','.');
                            }elseif($field[1]['kind'] == 'boolean'){
                                $rowitem = (boolean) $doc[$field[0]];
							}else{
								$rowitem = $doc[$field[0]];
							}

							if(isset($field[1]['attr'])){
								$attr = '';
								foreach ($field[1]['attr'] as $key => $value) {
									$attr .= $key.'="'.$value.'" ';
								}
								$row[] = '<span '.$attr.' >'.$rowitem.'</span>';
							}else{
								$row[] = $rowitem;
							}

						}


					}else{
						$row[] = '';
					}
				}
			}

            if($this->place_action == 'both'){
                $row[] = $actions;
            }

			$row['extra'] = $extra;

			$aadata[] = $row;

			$counter++;
		}

        $aadata = $this->rows_post_process($aadata, $this->aux_data);

        $sEcho = (int) Request::input('sEcho');

		$result = array(
			'sEcho'=>  $sEcho,
			'iTotalRecords'=>$count_all,
			'iTotalDisplayRecords'=> (is_null($count_display_all))?0:$count_display_all,
			'aaData'=>$aadata,
			'qrs'=>$q,
			'sort'=>array($sort_col=>$sort_dir)
		);

		return Response::json($result);
	}

    public function __SQLtableResponder()
    {
        set_time_limit(0);

        $fields = $this->fields;

        $count_all = 0;
        $count_display_all = 0;

        //print_r($fields);

        //print 'print '.$this->print.' paging '.$this->no_paging;


        //array_unshift($fields, array('select',array('kind'=>false)));
        array_unshift($fields, array('seq',array('kind'=>false)));

        //if($this->show_select == true){
        //    array_unshift($fields, array('select',array('kind'=>false)));
        //}

        if($this->place_action == 'both' || $this->place_action == 'first'){
            array_unshift($fields, array('action',array('kind'=>false)));
        }

        $pagestart = Request::input('iDisplayStart');
        $pagelength = Request::input('iDisplayLength');

        $limit = array($pagelength, $pagestart);

        $defsort = 1;
        $defdir = -1;

        $idx = 0;
        $q = array();

        $hilite = array();
        $hilite_replace = array();


        //$model = $this->model;

        //$table = $emodel->getTable();

        $model = DB::connection($this->sql_connection)->table($this->sql_table_name);

        $model = $this->SQL_additional_query($model);

        //$model = $this->SQL_make_join($model);

        for($i = 0;$i < count($fields);$i++){
            $idx = $i;

            //print_r($fields[$i]);

            $field = $fields[$i][0];
            $type = $fields[$i][1]['kind'];


            $qval = '';

            $sfields = explode('.',$field);
            $sub = '';
            if(count($sfields) > 1){
                $sub = $sfields[0];
                $subfield = $sfields[1];
            }

            if(Request::input('sSearch_'.$i))
            {
                if( $type == 'text'){
                    if($fields[$i][1]['query'] == 'like'){
                        $pos = $fields[$i][1]['pos'];
                        if($pos == 'both'){
                            //$model->whereRegex($field,'/'.Request::input('sSearch_'.$idx).'/i');
                            $model = $model->where($field,'like','%'.Request::input('sSearch_'.$idx).'%');
                            /*
                            if($sub == ''){
                                $model = $model->where($field,'like','%'.Request::input('sSearch_'.$idx).'%');
                            }else{
                                $model = $model->whereHas($sub, function($q) use ($subfield, $idx) {
                                    $q->where($subfield,'like','%'.Request::input('sSearch_'.$idx).'%');
                                });
                            }
                            */

                            $qval = new MongoRegex('/'.Request::input('sSearch_'.$idx).'/i');
                        }else if($pos == 'before'){
                            //$this->model->whereRegex($field,'/^'.Request::input('sSearch_'.$idx).'/i');
                            $model = $model->where($field,'like','%'.Request::input('sSearch_'.$idx));

                            $qval = new \MongoRegex('/^'.Request::input('sSearch_'.$idx).'/i');
                        }else if($pos == 'after'){
                            //$this->model->whereRegex($field,'/'.Request::input('sSearch_'.$idx).'$/i');
                            $model = $model->where($field,'like', Request::input('sSearch_'.$idx).'%');

                            $qval = new \MongoRegex('/'.Request::input('sSearch_'.$idx).'$/i');
                        }
                    }else{
                        $qval = Request::input('sSearch_'.$idx);

                        $model = $model->where($field,$qval);
                    }

                    $q[$field] = $qval;

                }elseif($type == 'numeric' || $type == 'currency'){
                    $str = Request::input('sSearch_'.$idx);

                    $sign = null;

                    $strval = trim(str_replace(array('<','>','='), '', $str));

                    $qval = (double)$strval;

                    /*
                    if(is_null($sign)){
                        $qval = new MongoInt32($strval);
                    }else{
                        $str = new MongoInt32($str);
                        $qval = array($sign=>$str);
                    }
                    */


                    if(strpos($str, "<=") !== false){
                        $sign = '$lte';

                        //$this->model->whereLte($field,$qval);
                        $model = $model->where($field,'<=',$qval);

                    }elseif(strpos($str, ">=") !== false){
                        $sign = '$gte';

                        //$this->model->whereGte($field,$qval);
                        $model = $model->where($field,'>=',$qval);

                    }elseif(strpos($str, ">") !== false){
                        $sign = '$gt';

                        //$this->model->whereGt($field,$qval);
                        $model = $model->where($field,'>',$qval);

                    }elseif(stripos($str, "<") !== false){
                        $sign = '$lt';

                        //$this->model->whereLt($field,$qval);
                        $model = $model->where($field,'<',$qval);

                    }else{

                        $model = $model->where($field,'=',$qval);

                    }

                    //print $sign;
                    if(!is_null($sign)){
                        $qval = array($sign=>$qval);
                    }

                    $q[$field] = $qval;

                }elseif($type == 'date'|| $type == 'datetime'){
                    $datestring = Request::input('sSearch_'.$idx);
                    $datestring = date('d-m-Y', $datestring / 1000);

                    if (($timestamp = $datestring) === false) {

                    } else {
                        //$daystart = new MongoDate(strtotime($datestring.' 00:00:00'));
                        //$dayend = new MongoDate(strtotime($datestring.' 23:59:59'));

                        $daystart = $datestring.' 00:00:00';
                        $dayend = $datestring.' 23:59:59';

                        //$qval = array($field =>array('$gte'=>$daystart,'$lte'=>$dayend));
                        //echo "$str == " . date('l dS \o\f F Y h:i:s A', $timestamp);

                        $model = $model->whereBetween($field,array($daystart,$dayend));

                    }
                    $qval = array('$gte'=>$daystart,'$lte'=>$dayend);
                    //$qval = Request::input('sSearch_'.$idx);

                    $q[$field] = $qval;
                }elseif($type == 'daterange'){
                    $datestring = Request::input('sSearch_'.$idx);

                    //print $datestring;

                    if($datestring != ''){
                        $dates = explode(' - ', $datestring);

                        if(count($dates) == 2){

                            $daystart = date('Y-m-d',strtotime($dates[0])).' 00:00:00';
                            $dayend = date('Y-m-d',strtotime($dates[1])).' 23:59:59';

                            //print $daystart;
                            //$qval = array($field =>array('$gte'=>$daystart,'$lte'=>$dayend));

                            $qval = array('$gte'=>$daystart,'$lte'=>$dayend);
                            //$qval = Request::input('sSearch_'.$idx);

                            $q[$field] = $qval;

                            $model = $model->whereBetween($field,array($daystart,$dayend));


                        }

                    }

                }elseif($type == 'datetimerange'){
                    $datestring = Request::input('sSearch_'.$idx);

                    if($datestring != ''){
                        $dates = explode(' - ', $datestring);

                        //print_r($dates);

                        if(count($dates) == 2){
                            $daystart = date('Y-m-d H:i:s',strtotime($dates[0]));
                            $dayend = date('Y-m-d H:i:s',strtotime($dates[1]));

                            //$qval = array($field =>array('$gte'=>$daystart,'$lte'=>$dayend));

                            $qval = array('$gte'=>$daystart,'$lte'=>$dayend);
                            //$qval = Request::input('sSearch_'.$idx);

                            $model = $model->whereBetween($field,array($daystart,$dayend));

                            $q[$field] = $qval;
                        }

                    }

                }


            }

        }

        /* first column is always sequence number, so must be omitted */

        $fidx = Request::input('iSortCol_0') - 1;

        $fidx = ($fidx == -1 )?0:$fidx;

        if(Request::input('iSortCol_0') == 0){
            $sort_col = $this->def_order_by;

            $sort_dir = $this->def_order_dir;
        }else{
            $sort_col = $fields[$fidx][0];

            $sort_dir = Request::input('sSortDir_0');

        }


        /*
        if(count($q) > 0){
            $results = $model->skip( $pagestart )->take( $pagelength )->orderBy($sort_col, $sort_dir )->get();
            $count_display_all = $model->count();
        }else{
            $results = $model->find(array(),array(),array($sort_col=>$sort_dir),$limit);
            $count_display_all = $model->count();
        }
        */

        //$model->where('docFormat','picture');

        $count_all = $model->count();
        //$count_display_all = $model->count();
        $count_display_all = $count_all;

        $this->aux_data = $this->SQL_before_paging($model);

        if($this->no_paging == true){
            $results = $model->orderBy($sort_col, $sort_dir )->get();
        }else{
            $results = $model->skip( $pagestart )->take( $pagelength )->orderBy($sort_col, $sort_dir )->get();
        }


        //$model = $this->SQL_make_join($model);

        /*
        if(is_array($q) && count($q) > 0){

            $count_display_all = $model->count();

            $results = $model->skip( $pagestart )->take( $pagelength )->orderBy($sort_col, $sort_dir )->get();

            //$last_query = $this->get_last_query();
            //$last_query = DB::getQueryLog();


        }else{

            $count_display_all = $model->count();

            $results = $model->skip( $pagestart )->take( $pagelength )->orderBy($sort_col, $sort_dir )->get();

            //$last_query = $this->get_last_query();
            //$last_query = DB::getQueryLog();


        }*/

        //print_r($model);

        //exit();

        //$queries = DB::getQueryLog();
        $last_query = $model;

        //print_r($results);


        $aadata = array();

        $form = $this->form;

        $counter = 1 + $pagestart;


        foreach ($results as $doc) {

            $doc = (array) $doc;

            //print_r($doc);

            $extra = $doc;

            //$select = Former::checkbox('sel_'.$doc['_id'])->check(false)->id($doc['_id'])->class('selector');
            $actionMaker = $this->makeActions;

            $actions = $this->$actionMaker($doc);

            $row = array();

            $row[] = $counter;

            if($this->show_select == true){
                //$sel = Former::checkbox('sel_'.$doc['_id'])->check(false)->label(false)->id($doc['_id'])->class('selector')->__toString();
                $sel = '<input type="checkbox" name="sel_'.$doc[$this->sql_key].'" id="'.$doc[$this->sql_key].'" value="'.$doc[$this->sql_key].'" class="selector" />';
                $row[] = $sel;
            }

            if($this->place_action == 'both' || $this->place_action == 'first'){
                $row[] = $actions;
            }


            foreach($fields as $field){

                //$join = (isset($fields[$i][1]['join']))?$fields[$i][1]['join']:false;

                if($field[1]['kind'] != false && $field[1]['show'] == true){
                    /*
                    $fieldarray = explode('.',$field[0]);
                    if(is_array($fieldarray) && count($fieldarray) > 1){
                        $fieldarray = implode('\'][\'',$fieldarray);
                        $cstring = '$label = (isset($doc[\''.$fieldarray.'\']))?true:false;';
                        eval($cstring);
                    }else{
                        $label = (isset($doc[$field[0]]))?true:false;
                    }
                    */

                    $label = (isset($doc[$field[0]]) || isset($field[1]['alias']) )?true:false;


                    if($label){

                        if( isset($field[1]['callback']) && $field[1]['callback'] != ''){
                            $callback = $field[1]['callback'];
                            $row[] = $this->$callback($doc, $field[0]);
                        }elseif( isset($field[1]['alias']) && $field[1]['alias'] != ''){
                            $row[] = $doc[$field[1]['alias']];
                        }else{
                            if($field[1]['kind'] == 'datetime' || $field[1]['kind'] == 'datetimerange'){
                                if($doc[$field[0]] instanceof MongoDate){
                                    $rowitem = date('d-m-Y H:i:s',$doc[$field[0]]->sec);
                                }elseif ($doc[$field[0]] instanceof Date) {
                                    $rowitem = date('d-m-Y H:i:s',$doc[$field[0]]);
                                }else{
                                    //$rowitem = $doc[$field[0]];
                                    if(is_array($doc[$field[0]])){
                                        $rowitem = date('d-m-Y H:i:s', time() );
                                    }else{
                                        $rowitem = date('d-m-Y H:i:s',strtotime($doc[$field[0]]) );
                                    }
                                }
                            }elseif($field[1]['kind'] == 'date' || $field[1]['kind'] == 'daterange'){
                                if($doc[$field[0]] instanceof MongoDate){
                                    $rowitem = date('d-m-Y',$doc[$field[0]]->sec);
                                }elseif ($doc[$field[0]] instanceof Date) {
                                    $rowitem = date('d-m-Y',$doc[$field[0]]);
                                }else{
                                    //$rowitem = $doc[$field[0]];
                                    $rowitem = date('d-m-Y',strtotime($doc[$field[0]]) );
                                }
                            }elseif($field[1]['kind'] == 'currency'){
                                $num = (double) $doc[$field[0]];
                                $rowitem = number_format($num,2,',','.');
                            }else{
                                $rowitem = $doc[$field[0]];
                            }

                            if(isset($field[1]['attr'])){
                                $attr = '';
                                foreach ($field[1]['attr'] as $key => $value) {
                                    $attr .= $key.'="'.$value.'" ';
                                }
                                $row[] = '<span '.$attr.' >'.$rowitem.'</span>';
                            }else{
                                $row[] = $rowitem;
                            }

                        }


                    }else{
                        $row[] = '';
                    }
                }
            }

            if($this->place_action == 'both'){
                $row[] = $actions;
            }

            $row['extra'] = $extra;

            $aadata[] = $row;

            $counter++;
        }

        //print_r($this->aux_data);

        $aadata = $this->rows_post_process($aadata, $this->aux_data);

        $sEcho = (int) Request::input('sEcho');


        if($this->print == true){
            $result = array(
                'sEcho'=>  $sEcho,
                'iTotalRecords'=>$count_all,
                'iTotalDisplayRecords'=> (is_null($count_display_all))?0:$count_display_all,
                'aaData'=>$aadata,
                'sort'=>array($sort_col=>$sort_dir)
            );
            return $result;
        }else{
            $result = array(
                'sEcho'=>  $sEcho,
                'iTotalRecords'=>$count_all,
                'iTotalDisplayRecords'=> (is_null($count_display_all))?0:$count_display_all,
                'aaData'=>$aadata,
                'qrs'=>$last_query,
                'sort'=>array($sort_col=>$sort_dir)
            );

            return Response::json($result);
        }

    }


    public function SQLtableResponder()
    {
        set_time_limit(0);

        $fields = $this->fields;

        $count_all = 0;
        $count_display_all = 0;

        //array_unshift($fields, array('select',array('kind'=>false)));
        array_unshift($fields, array('seq',array('kind'=>false)));


        //if($this->show_select == true){
        //    array_unshift($fields, array('select',array('kind'=>false)));
        //}


        if($this->place_action == 'both' || $this->place_action == 'first'){
            array_unshift($fields, array('action',array('kind'=>false)));
        }

        $pagestart = Request::input('iDisplayStart');
        $pagelength = Request::input('iDisplayLength');

        $limit = array($pagelength, $pagestart);

        $defsort = 1;
        $defdir = -1;

        $idx = 0;
        $q = array();

        $hilite = array();
        $hilite_replace = array();


        //$model = $this->model;

        //$table = $emodel->getTable();

        $model = DB::connection($this->sql_connection)->table($this->sql_table_name);

        $count_all = $model->count();

        $model = $this->SQL_additional_query($model);

        //$model = $this->SQL_make_join($model);

        $comres = $this->SQLcompileSearch($fields, $model);

        $model = $comres['model'];
        $q = $comres['q'];


        /* first column is always sequence number, so must be omitted */

        $fidx = Request::input('iSortCol_0') - 1;

        $fidx = ($fidx == -1 )?0:$fidx;

        if(Request::input('iSortCol_0') == 0){
            $sort_col = $this->def_order_by;

            $sort_dir = $this->def_order_dir;
        }else{
            $sort_col = $fields[$fidx][0];

            $sort_dir = Request::input('sSortDir_0');

        }


        /*
        if(count($q) > 0){
            $results = $model->skip( $pagestart )->take( $pagelength )->orderBy($sort_col, $sort_dir )->get();
            $count_display_all = $model->count();
        }else{
            $results = $model->find(array(),array(),array($sort_col=>$sort_dir),$limit);
            $count_display_all = $model->count();
        }
        */

        //$model->where('docFormat','picture');

        //$count_display_all = $model->count();
        $count_display_all = $model->count();

        $this->aux_data = $this->SQL_before_paging($model);

        if($this->no_paging == true){
            $results = $model->orderBy($sort_col, $sort_dir )->get();
        }else{
            $results = $model->skip( $pagestart )->take( $pagelength )->orderBy($sort_col, $sort_dir )->get();
        }


        //$model = $this->SQL_make_join($model);

        /*
        if(is_array($q) && count($q) > 0){

            $count_display_all = $model->count();

            $results = $model->skip( $pagestart )->take( $pagelength )->orderBy($sort_col, $sort_dir )->get();

            //$last_query = $this->get_last_query();
            //$last_query = DB::getQueryLog();


        }else{

            $count_display_all = $model->count();

            $results = $model->skip( $pagestart )->take( $pagelength )->orderBy($sort_col, $sort_dir )->get();

            //$last_query = $this->get_last_query();
            //$last_query = DB::getQueryLog();


        }*/

        //print_r($model);

        //exit();

        //$queries = DB::getQueryLog();
        $last_query = $model;

        //print_r($results);


        $aadata = array();

        $form = $this->form;

        $counter = 1 + $pagestart;


        foreach ($results as $doc) {

            $doc = (array) $doc;

            //print_r($doc);

            $extra = $doc;

            //$select = Former::checkbox('sel_'.$doc['_id'])->check(false)->id($doc['_id'])->class('selector');
            $actionMaker = $this->makeActions;

            $actions = $this->$actionMaker($doc);

            $row = array();

            $row[] = $counter;

            if($this->show_select == true){
                //$sel = Former::checkbox('sel_'.$doc['_id'])->check(false)->label(false)->id($doc['_id'])->class('selector')->__toString();
                $sel = '<input type="checkbox" name="sel_'.$doc[$this->sql_key].'" id="'.$doc[$this->sql_key].'" value="'.$doc[$this->sql_key].'" class="selector" />';
                $row[] = $sel;
            }

            if($this->place_action == 'both' || $this->place_action == 'first'){
                $row[] = $actions;
            }


            foreach($fields as $field){

                //$join = (isset($fields[$i][1]['join']))?$fields[$i][1]['join']:false;

                if($field[1]['kind'] != false && $field[1]['show'] == true){
                    /*
                    $fieldarray = explode('.',$field[0]);
                    if(is_array($fieldarray) && count($fieldarray) > 1){
                        $fieldarray = implode('\'][\'',$fieldarray);
                        $cstring = '$label = (isset($doc[\''.$fieldarray.'\']))?true:false;';
                        eval($cstring);
                    }else{
                        $label = (isset($doc[$field[0]]))?true:false;
                    }
                    */

                    $label = (isset($doc[$field[0]]) || isset($field[1]['alias']) )?true:false;


                    if($label){

                        if( isset($field[1]['callback']) && $field[1]['callback'] != ''){
                            $callback = $field[1]['callback'];
                            $row[] = $this->$callback($doc, $field[0]);
                        }elseif( isset($field[1]['alias']) && $field[1]['alias'] != ''){
                            $row[] = $doc[$field[1]['alias']];
                        }else{
                            if($field[1]['kind'] == 'datetime' || $field[1]['kind'] == 'datetimerange'){

                                if( !($doc[$field[0]] == '' || $doc[$field[0]] == '0000-00-00 00:00:00')){

                                    if($doc[$field[0]] instanceof MongoDate){
                                        $rowitem = date('d-m-Y H:i:s',$doc[$field[0]]->sec);
                                    }elseif ($doc[$field[0]] instanceof Date) {
                                        $rowitem = date('d-m-Y H:i:s',$doc[$field[0]]);
                                    }else{
                                        //$rowitem = $doc[$field[0]];
                                        if(is_array($doc[$field[0]])){
                                            $rowitem = date('d-m-Y H:i:s', time() );
                                        }else{
                                            $rowitem = date('d-m-Y H:i:s',strtotime($doc[$field[0]]) );
                                        }
                                    }

                                }
                            }elseif($field[1]['kind'] == 'date' || $field[1]['kind'] == 'daterange'){
                                //print_r($doc[$field[0]]);
                                if( !($doc[$field[0]] == '' || $doc[$field[0]] == '0000-00-00 00:00:00')){
                                    if($doc[$field[0]] instanceof MongoDate){
                                        $rowitem = date('d-m-Y',$doc[$field[0]]->sec);
                                    }elseif ($doc[$field[0]] instanceof Date) {
                                        $rowitem = date('d-m-Y',$doc[$field[0]]);
                                    }else{
                                        //$rowitem = $doc[$field[0]];
                                        $rowitem = date('d-m-Y',strtotime($doc[$field[0]]) );
                                    }
                                }

                            }elseif($field[1]['kind'] == 'currency'){
                                $num = (double) $doc[$field[0]];
                                $rowitem = number_format($num,2,',','.');
                            }else{
                                $rowitem = $doc[$field[0]];
                            }

                            if(isset($field[1]['attr'])){
                                $attr = '';
                                foreach ($field[1]['attr'] as $key => $value) {
                                    $attr .= $key.'="'.$value.'" ';
                                }
                                $row[] = '<span '.$attr.' >'.$rowitem.'</span>';
                            }else{
                                $row[] = $rowitem;
                            }

                        }


                    }else{
                        $row[] = '';
                    }
                }
            }

            if($this->place_action == 'both'){
                $row[] = $actions;
            }

            $row['extra'] = $extra;

            $aadata[] = $row;

            $counter++;
        }

        //print_r($this->aux_data);

        $aadata = $this->rows_post_process($aadata, $this->aux_data);

        $sEcho = (int) Request::input('sEcho');


        if($this->print == true){
            $result = array(
                'sEcho'=>  $sEcho,
                'iTotalRecords'=>$count_all,
                'iTotalDisplayRecords'=> (is_null($count_display_all))?0:$count_display_all,
                'aaData'=>$aadata,
                'sort'=>array($sort_col=>$sort_dir)
            );
            return $result;
        }else{
            $result = array(
                'sEcho'=>  $sEcho,
                'iTotalRecords'=>$count_all,
                'iTotalDisplayRecords'=> (is_null($count_display_all))?0:$count_display_all,
                'aaData'=>$aadata,
                'qrs'=>$last_query,
                'sort'=>array($sort_col=>$sort_dir)
            );

            return Response::json($result);
        }

    }

    public function SQLcompileSearch($fields,$model){

        $q = array();

        for($i = 1;$i < count($fields);$i++){
            $idx = $i;

            //print_r($fields[$i]);

            $field = $fields[$i][0];
            $type = $fields[$i][1]['kind'];


            $qval = '';

            $sfields = explode('.',$field);
            $sub = '';
            if(count($sfields) > 1){
                $sub = $sfields[0];
                $subfield = $sfields[1];
            }

            if(Request::input('sSearch_'.$i ))
            {
                $multi = (isset($fields[$i][1]['multi']))?$fields[$i][1]['multi']:false;
                $multirel = (isset($fields[$i][1]['multi']))?$fields[$i][1]['multirel']:'AND';

                if( $type == 'text'){
                    if($fields[$i][1]['query'] == 'like'){
                        $pos = $fields[$i][1]['pos'];
                        if($pos == 'both'){

                            $model = $model->where(function($q) use($field,$multi,$multirel,$idx){

                                if($multi){
                                    $n = 0;
                                    foreach($multi as $mf){
                                        if($n == 0){
                                            $q = $q->where($mf,'like','%'.Request::input('sSearch_'.$idx).'%');
                                        }else{
                                            if($multirel == 'OR'){
                                                $q = $q->orWhere($mf,'like','%'.Request::input('sSearch_'.$idx).'%');
                                            }else{
                                                $q = $q->where($mf,'like','%'.Request::input('sSearch_'.$idx).'%');
                                            }
                                        }
                                        $n++;
                                    }
                                }else{
                                    $q->where($field,'like','%'.Request::input('sSearch_'.$idx).'%');
                                    $qval = new MongoRegex('/'.Request::input('sSearch_'.$idx).'/i');
                                }

                            });

                        }else if($pos == 'before'){

                            $model = $model->where(function($q) use($field,$multi,$multirel,$idx){

                                if($multi){
                                    $n = 0;
                                    foreach($multi as $mf){
                                        if($n == 0){
                                            $q = $q->where($mf,'like','%'.Request::input('sSearch_'.$idx));
                                        }else{
                                            if($multirel == 'OR'){
                                                $q = $q->orWhere($mf,'like','%'.Request::input('sSearch_'.$idx));
                                            }else{
                                                $q = $q->where($mf,'like','%'.Request::input('sSearch_'.$idx));
                                            }
                                        }
                                        $n++;
                                    }
                                }else{
                                    $q->where($field,'like','%'.Request::input('sSearch_'.$idx));
                                    $qval = new MongoRegex('/'.Request::input('sSearch_'.$idx).'/i');
                                }

                            });


                        }else if($pos == 'after'){

                            $model = $model->where(function($q) use($field,$multi,$multirel,$idx){

                                if($multi){
                                    $n = 0;
                                    foreach($multi as $mf){
                                        if($n == 0){
                                            $q = $q->where($mf,'like',Request::input('sSearch_'.$idx).'%');
                                        }else{
                                            if($multirel == 'OR'){
                                                $q = $q->orWhere($mf,'like',Request::input('sSearch_'.$idx).'%');
                                            }else{
                                                $q = $q->where($mf,'like',Request::input('sSearch_'.$idx).'%');
                                            }
                                        }
                                        $n++;
                                    }
                                }else{
                                    $q->where($field,'like',Request::input('sSearch_'.$idx).'%');
                                    $qval = new MongoRegex('/'.Request::input('sSearch_'.$idx).'/i');
                                }

                            });

                        }
                    }else{

                        $model = $model->where(function($q) use($field,$multi,$multirel,$idx){

                            if($multi){
                                $n = 0;
                                foreach($multi as $mf){
                                    if($n == 0){
                                        $q = $q->where($mf,'=',Request::input('sSearch_'.$idx));
                                    }else{
                                        if($multirel == 'OR'){
                                            $q = $q->orWhere($mf,'=',Request::input('sSearch_'.$idx));
                                        }else{
                                            $q = $q->where($mf,'=',Request::input('sSearch_'.$idx));
                                        }
                                    }
                                    $n++;
                                }
                            }else{
                                $q->where($field,'=',Request::input('sSearch_'.$idx));
                                $qval = new MongoRegex('/'.Request::input('sSearch_'.$idx).'/i');
                            }

                        });


                    }

                    $q[$field] = $qval;

                }elseif($type == 'numeric' || $type == 'currency'){

                    $str = Request::input('sSearch_'.$idx);

                    $sign = null;

                    $strval = trim(str_replace(array('<','>','='), '', $str));

                    $qval = (double)$strval;

                    if(strpos($str, "<=") !== false){
                        $sign = '<=';
                    }elseif(strpos($str, ">=") !== false){
                        $sign = '>=';
                    }elseif(strpos($str, ">") !== false){
                        $sign = '>';
                    }elseif(stripos($str, "<") !== false){
                        $sign = '<';
                    }else{
                        $sign = '=';
                    }


                    $model = $model->where(function($q) use($field,$qval,$sign,$multi,$multirel,$idx){

                        if($multi){
                            $n = 0;
                            foreach($multi as $mf){
                                if($n == 0){
                                    $q = $q->where($mf,$sign,$qval);
                                }else{
                                    if($multirel == 'OR'){
                                        $q = $q->orWhere($mf,$sign,$qval);
                                    }else{
                                        $q = $q->where($mf,$sign,$qval);
                                    }
                                }
                                $n++;
                            }
                        }else{
                            $q->where($field,$sign,$qval);
                        }

                    });



                }elseif($type == 'date'|| $type == 'datetime'){
                    $datestring = Request::input('sSearch_'.$idx);
                    $datestring = date('d-m-Y', $datestring / 1000);

                    if (($timestamp = $datestring) === false) {

                    } else {
                        //$daystart = new MongoDate(strtotime($datestring.' 00:00:00'));
                        //$dayend = new MongoDate(strtotime($datestring.' 23:59:59'));

                        $daystart = $datestring.' 00:00:00';
                        $dayend = $datestring.' 23:59:59';

                        //$qval = array($field =>array('$gte'=>$daystart,'$lte'=>$dayend));
                        //echo "$str == " . date('l dS \o\f F Y h:i:s A', $timestamp);

                        $model = $model->where(function($q) use($field,$daystart,$dayend){
                            $q->whereBetween($field,array($daystart,$dayend));
                        });

                    }
                    $qval = array('$gte'=>$daystart,'$lte'=>$dayend);
                    //$qval = Request::input('sSearch_'.$idx);

                    $q[$field] = $qval;
                }elseif($type == 'daterange'){
                    $datestring = Request::input('sSearch_'.$idx);

                    //print $datestring;

                    if($datestring != ''){
                        $dates = explode(' - ', $datestring);

                        if(count($dates) == 2){

                            $daystart = date('Y-m-d',strtotime($dates[0])).' 00:00:00';
                            $dayend = date('Y-m-d',strtotime($dates[1])).' 23:59:59';

                            //print $daystart;
                            //$qval = array($field =>array('$gte'=>$daystart,'$lte'=>$dayend));

                            $qval = array('$gte'=>$daystart,'$lte'=>$dayend);
                            //$qval = Request::input('sSearch_'.$idx);

                            $q[$field] = $qval;


                            $model = $model->where(function($q) use($field,$daystart,$dayend){
                                $q->whereBetween($field,array($daystart,$dayend));
                            });


                        }

                    }

                }elseif($type == 'datetimerange'){
                    $datestring = Request::input('sSearch_'.$idx);

                    if($datestring != ''){
                        $dates = explode(' - ', $datestring);

                        //print_r($dates);

                        if(count($dates) == 2){
                            $daystart = date('Y-m-d H:i:s',strtotime($dates[0]));
                            $dayend = date('Y-m-d H:i:s',strtotime($dates[1]));

                            //$qval = array($field =>array('$gte'=>$daystart,'$lte'=>$dayend));

                            $qval = array('$gte'=>$daystart,'$lte'=>$dayend);
                            //$qval = Request::input('sSearch_'.$idx);

                            //$model = $model->whereBetween($field,array($daystart,$dayend));

                            $model = $model->where(function($q) use($field,$daystart,$dayend){
                                $q->whereBetween($field,array($daystart,$dayend));
                            });

                            $q[$field] = $qval;
                        }

                    }

                }


            }

        }

        return array('model'=>$model, 'q'=>$q);
    }


    public function MongoCompileSearch($fields,$model){

        $q = array();

        for($i = 1;$i < count($fields);$i++){
            $idx = $i;

            //print_r($fields[$i]);

            $field = $fields[$i][0];
            $type = $fields[$i][1]['kind'];


            $qval = '';

            $sfields = explode('.',$field);
            $sub = '';
            if(count($sfields) > 1){
                $sub = $sfields[0];
                $subfield = $sfields[1];
            }

            if(Request::input('sSearch_'.$i ))
            {
                $multi = (isset($fields[$i][1]['multi']))?$fields[$i][1]['multi']:false;
                $multirel = (isset($fields[$i][1]['multi']))?$fields[$i][1]['multirel']:'AND';

                if( $type == 'text'){
                    if($fields[$i][1]['query'] == 'like'){
                        $pos = $fields[$i][1]['pos'];
                        if($pos == 'both'){

                            $model = $model->where(function($q) use($field,$multi,$multirel,$idx){

                                if($multi){
                                    $n = 0;
                                    foreach($multi as $mf){
                                        if($n == 0){
                                            $q = $q->where($mf,'like','%'.Request::input('sSearch_'.$idx).'%');
                                        }else{
                                            if($multirel == 'OR'){
                                                $q = $q->orWhere($mf,'like','%'.Request::input('sSearch_'.$idx).'%');
                                            }else{
                                                $q = $q->where($mf,'like','%'.Request::input('sSearch_'.$idx).'%');
                                            }
                                        }
                                        $n++;
                                    }
                                }else{
                                    //$q->where($field,'like','%'.Request::input('sSearch_'.$idx).'%');
                                    $q->where($field,'like','%'.Request::input('sSearch_'.$idx).'%');
                                    $qval = new MongoRegex('/'.Request::input('sSearch_'.$idx).'/i');
                                }

                            });

                        }else if($pos == 'before'){

                            $model = $model->where(function($q) use($field,$multi,$multirel,$idx){

                                if($multi){
                                    $n = 0;
                                    foreach($multi as $mf){
                                        if($n == 0){
                                            $q = $q->where($mf,'like','%'.Request::input('sSearch_'.$idx));
                                        }else{
                                            if($multirel == 'OR'){
                                                $q = $q->orWhere($mf,'like','%'.Request::input('sSearch_'.$idx));
                                            }else{
                                                $q = $q->where($mf,'like','%'.Request::input('sSearch_'.$idx));
                                            }
                                        }
                                        $n++;
                                    }
                                }else{
                                    $q->where($field,'like','%'.Request::input('sSearch_'.$idx));
                                    $qval = new MongoRegex('/'.Request::input('sSearch_'.$idx).'/i');
                                }

                            });


                        }else if($pos == 'after'){

                            $model = $model->where(function($q) use($field,$multi,$multirel,$idx){

                                if($multi){
                                    $n = 0;
                                    foreach($multi as $mf){
                                        if($n == 0){
                                            $q = $q->where($mf,'like',Request::input('sSearch_'.$idx).'%');
                                        }else{
                                            if($multirel == 'OR'){
                                                $q = $q->orWhere($mf,'like',Request::input('sSearch_'.$idx).'%');
                                            }else{
                                                $q = $q->where($mf,'like',Request::input('sSearch_'.$idx).'%');
                                            }
                                        }
                                        $n++;
                                    }
                                }else{
                                    $q->where($field,'like',Request::input('sSearch_'.$idx).'%');
                                    $qval = new MongoRegex('/'.Request::input('sSearch_'.$idx).'/i');
                                }

                            });

                        }
                    }else{

                        $model = $model->where(function($q) use($field,$multi,$multirel,$idx){

                            if($multi){
                                $n = 0;
                                foreach($multi as $mf){
                                    if($n == 0){
                                        $q = $q->where($mf,'=',Request::input('sSearch_'.$idx));
                                    }else{
                                        if($multirel == 'OR'){
                                            $q = $q->orWhere($mf,'=',Request::input('sSearch_'.$idx));
                                        }else{
                                            $q = $q->where($mf,'=',Request::input('sSearch_'.$idx));
                                        }
                                    }
                                    $n++;
                                }
                            }else{
                                $q->where($field,'=',Request::input('sSearch_'.$idx));
                                $qval = new MongoRegex('/'.Request::input('sSearch_'.$idx).'/i');
                            }

                        });


                    }

                    $q[$field] = $qval;
                }elseif($type == 'boolean'){
                    $state = Request::input('sSearch_'.$idx);

                    $state = (strtolower($state) == 'false' )?false:$state;
                    $model = $model->where($field,'=', (bool) $state );

                }elseif($type == 'numeric' || $type == 'currency'){

                    $str = Request::input('sSearch_'.$idx);

                    $sign = null;

                    $strval = trim(str_replace(array('<','>','='), '', $str));

                    $qval = (double)$strval;

                    if(strpos($str, "<=") !== false){
                        $sign = '<=';
                    }elseif(strpos($str, ">=") !== false){
                        $sign = '>=';
                    }elseif(strpos($str, ">") !== false){
                        $sign = '>';
                    }elseif(stripos($str, "<") !== false){
                        $sign = '<';
                    }else{
                        $sign = '=';
                    }


                    $model = $model->where(function($q) use($field,$qval,$sign,$multi,$multirel,$idx){

                        if($multi){
                            $n = 0;
                            foreach($multi as $mf){
                                if($n == 0){
                                    $q = $q->where($mf,$sign,$qval);
                                }else{
                                    if($multirel == 'OR'){
                                        $q = $q->orWhere($mf,$sign,$qval);
                                    }else{
                                        $q = $q->where($mf,$sign,$qval);
                                    }
                                }
                                $n++;
                            }
                        }else{
                            $q->where($field,$sign,$qval);
                        }

                    });



                }elseif($type == 'date'|| $type == 'datetime'){
                    $datestring = Request::input('sSearch_'.$idx);
                    $datestring = date('d-m-Y', $datestring / 1000);

                    if (($timestamp = $datestring) === false) {

                    } else {
                        $daystart = new MongoDate(strtotime($datestring.' 00:00:00'));
                        $dayend = new MongoDate(strtotime($datestring.' 23:59:59'));

                        //$daystart = $datestring.' 00:00:00';
                        //$dayend = $datestring.' 23:59:59';

                        //$qval = array($field =>array('$gte'=>$daystart,'$lte'=>$dayend));
                        //echo "$str == " . date('l dS \o\f F Y h:i:s A', $timestamp);

                        $model = $model->where(function($q) use($field,$daystart,$dayend){
                            $q->whereBetween($field,array($daystart,$dayend));
                        });

                    }
                    $qval = array('$gte'=>$daystart,'$lte'=>$dayend);
                    //$qval = Request::input('sSearch_'.$idx);

                    $q[$field] = $qval;
                }elseif($type == 'daterange'){
                    $datestring = Request::input('sSearch_'.$idx);

                    //print $datestring;

                    if($datestring != ''){
                        $dates = explode(' - ', $datestring);

                        if(count($dates) == 2){

                            //$daystart = date('Y-m-d',strtotime($dates[0])).' 00:00:00';
                            //$dayend = date('Y-m-d',strtotime($dates[1])).' 23:59:59';

                            $daystart = new MongoDate( strtotime($dates[0].' 00:00:00') );
                            $dayend = new MongoDate( strtotime($dates[1].' 23:59:59') );

                            //print $daystart;
                            //$qval = array($field =>array('$gte'=>$daystart,'$lte'=>$dayend));

                            $qval = array('$gte'=>$daystart,'$lte'=>$dayend);
                            //$qval = Request::input('sSearch_'.$idx);

                            $q[$field] = $qval;


                            $model = $model->where(function($q) use($field,$daystart,$dayend){
                                $q->whereBetween($field,array($daystart,$dayend));
                            });


                        }

                    }

                }elseif($type == 'datetimerange'){
                    $datestring = Request::input('sSearch_'.$idx);

                    if($datestring != ''){
                        $dates = explode(' - ', $datestring);

                        //print_r($dates);

                        if(count($dates) == 2){
                            //$daystart = date('Y-m-d H:i:s',strtotime($dates[0]));
                            //$dayend = date('Y-m-d H:i:s',strtotime($dates[1]));

                            if(stripos($dates[0], ':' ) === false){
                                $datestart = $dates[0].' 00:00:00';
                            }else{
                                $datestart = $dates[0];
                            }

                            if(stripos($dates[1], ':' ) === false){
                                $dateend = $dates[1].' 23:59:59';
                            }else{
                                $dateend = $dates[1];
                            }

                            $daystart = new MongoDate( strtotime($datestart) );
                            $dayend = new MongoDate( strtotime($dateend) );

                            //$qval = array($field =>array('$gte'=>$daystart,'$lte'=>$dayend));

                            $qval = array('$gte'=>$daystart,'$lte'=>$dayend);
                            //$qval = Request::input('sSearch_'.$idx);

                            //$model = $model->whereBetween($field,array($daystart,$dayend));

                            $model = $model->where(function($q) use($field,$daystart,$dayend){
                                $q->whereBetween($field,array($daystart,$dayend));
                            });

                            $q[$field] = $qval;
                        }

                    }

                }


            }

        }

        return array('model'=>$model, 'q'=>$q);
    }

    public function DLcompileSearch($fields,$model,$infilter){

        $q = array();
        $inputarray = array();

        //print_r($fields);
        //print_r($infilter);

        for($i = 0;$i < count($fields);$i++){
            $idx = $i;

            $field = $fields[$i][0];
            $type = $fields[$i][1]['kind'];

            if(isset($infilter[$i])){
                $inputarray[$fields[$i][0]] = $infilter[$i];
            }

            //print $field."\r\n";

            $qval = '';

            $sfields = explode('.',$field);
            $sub = '';
            if(count($sfields) > 1){
                $sub = $sfields[0];
                $subfield = $sfields[1];
            }

            if( isset($infilter[$i]) && $infilter[$i] != '' )
            {

                //print $field.':'.$infilter[$i]."\r\n";
                //print $infilter[$i];

                $multi = (isset($fields[$i][1]['multi']))?$fields[$i][1]['multi']:false;
                $multirel = (isset($fields[$i][1]['multi']))?$fields[$i][1]['multirel']:'AND';

                if( $type == 'text'){
                    if($fields[$i][1]['query'] == 'like'){
                        $pos = $fields[$i][1]['pos'];
                        if($pos == 'both'){

                            $model = $model->where(function($q) use($infilter,$field,$multi,$multirel,$idx){

                                if($multi){
                                    $n = 0;
                                    foreach($multi as $mf){
                                        if($n == 0){
                                            $q = $q->where($mf,'like','%'.$infilter[$idx].'%');
                                        }else{
                                            if($multirel == 'OR'){
                                                $q = $q->orWhere($mf,'like','%'.$infilter[$idx].'%');
                                            }else{
                                                $q = $q->where($mf,'like','%'.$infilter[$idx].'%');
                                            }
                                        }
                                        $n++;
                                    }
                                }else{
                                    $q->where($field,'like','%'.$infilter[$idx].'%');
                                    $qval = new MongoRegex('/'.$infilter[$idx].'/i');
                                }

                            });

                        }else if($pos == 'before'){

                            $model = $model->where(function($q) use($infilter,$field,$multi,$multirel,$idx){

                                if($multi){
                                    $n = 0;
                                    foreach($multi as $mf){
                                        if($n == 0){
                                            $q = $q->where($mf,'like','%'.$infilter[$idx]);
                                        }else{
                                            if($multirel == 'OR'){
                                                $q = $q->orWhere($mf,'like','%'.$infilter[$idx]);
                                            }else{
                                                $q = $q->where($mf,'like','%'.$infilter[$idx]);
                                            }
                                        }
                                        $n++;
                                    }
                                }else{
                                    $q->where($field,'like','%'.$infilter[$idx]);
                                    $qval = new MongoRegex('/'.$infilter[$idx].'/i');
                                }

                            });


                        }else if($pos == 'after'){

                            $model = $model->where(function($q) use($infilter,$field,$multi,$multirel,$idx){

                                if($multi){
                                    $n = 0;
                                    foreach($multi as $mf){
                                        if($n == 0){
                                            $q = $q->where($mf,'like',$infilter[$idx].'%');
                                        }else{
                                            if($multirel == 'OR'){
                                                $q = $q->orWhere($mf,'like',$infilter[$idx].'%');
                                            }else{
                                                $q = $q->where($mf,'like',$infilter[$idx].'%');
                                            }
                                        }
                                        $n++;
                                    }
                                }else{
                                    $q->where($field,'like',$infilter[$idx].'%');
                                    $qval = new MongoRegex('/'.$infilter[$idx].'/i');
                                }

                            });

                        }
                    }else{

                        $model = $model->where(function($q) use($infilter,$field,$multi,$multirel,$idx){

                            if($multi){
                                $n = 0;
                                foreach($multi as $mf){
                                    if($n == 0){
                                        $q = $q->where($mf,'=',$infilter[$idx]);
                                    }else{
                                        if($multirel == 'OR'){
                                            $q = $q->orWhere($mf,'=',$infilter[$idx]);
                                        }else{
                                            $q = $q->where($mf,'=',$infilter[$idx]);
                                        }
                                    }
                                    $n++;
                                }
                            }else{
                                $q->where($field,'=',$infilter[$idx]);
                                $qval = new MongoRegex('/'.$infilter[$idx].'/i');
                            }

                        });


                    }

                    $q[$field] = $qval;

                }elseif($type == 'numeric' || $type == 'currency'){

                    $str = $infilter[$idx];

                    $sign = null;

                    $strval = trim(str_replace(array('<','>','='), '', $str));

                    $qval = (double)$strval;

                    if(strpos($str, "<=") !== false){
                        $sign = '<=';
                    }elseif(strpos($str, ">=") !== false){
                        $sign = '>=';
                    }elseif(strpos($str, ">") !== false){
                        $sign = '>';
                    }elseif(stripos($str, "<") !== false){
                        $sign = '<';
                    }else{
                        $sign = '=';
                    }


                    $model = $model->where(function($q) use($infilter,$field,$qval,$sign,$multi,$multirel,$idx){

                        if($multi){
                            $n = 0;
                            foreach($multi as $mf){
                                if($n == 0){
                                    $q = $q->where($mf,$sign,$qval);
                                }else{
                                    if($multirel == 'OR'){
                                        $q = $q->orWhere($mf,$sign,$qval);
                                    }else{
                                        $q = $q->where($mf,$sign,$qval);
                                    }
                                }
                                $n++;
                            }
                        }else{
                            $q->where($field,$sign,$qval);
                        }

                    });



                }elseif($type == 'date'|| $type == 'datetime'){
                    $datestring = $infilter[$idx];
                    $datestring = date('d-m-Y', $datestring / 1000);

                    if (($timestamp = $datestring) === false) {

                    } else {
                        $daystart = new MongoDate(strtotime($datestring.' 00:00:00'));
                        $dayend = new MongoDate(strtotime($datestring.' 23:59:59'));

                        //$daystart = $datestring.' 00:00:00';
                        //$dayend = $datestring.' 23:59:59';

                        //$qval = array($field =>array('$gte'=>$daystart,'$lte'=>$dayend));
                        //echo "$str == " . date('l dS \o\f F Y h:i:s A', $timestamp);

                        $model = $model->where(function($q) use($infilter,$field,$daystart,$dayend){
                            $q->whereBetween($field,array($daystart,$dayend));
                        });

                    }
                    $qval = array('$gte'=>$daystart,'$lte'=>$dayend);
                    //$qval = $infilter[$idx];

                    $q[$field] = $qval;
                }elseif($type == 'daterange'){
                    $datestring = $infilter[$idx];

                    //print $datestring;

                    if($datestring != ''){
                        $dates = explode(' - ', $datestring);

                        if(count($dates) == 2){

                            //$daystart = date('Y-m-d',strtotime($dates[0])).' 00:00:00';
                            //$dayend = date('Y-m-d',strtotime($dates[1])).' 23:59:59';

                            $daystart = new MongoDate( strtotime($dates[0].' 00:00:00') );
                            $dayend = new MongoDate( strtotime($dates[1].' 23:59:59') );

                            //print $daystart;
                            //$qval = array($field =>array('$gte'=>$daystart,'$lte'=>$dayend));

                            $qval = array('$gte'=>$daystart,'$lte'=>$dayend);
                            //$qval = $infilter[$idx];

                            $q[$field] = $qval;


                            $model = $model->where(function($q) use($infilter,$field,$daystart,$dayend){
                                $q->whereBetween($field,array($daystart,$dayend));
                            });


                        }

                    }

                }elseif($type == 'datetimerange'){
                    $datestring = $infilter[$idx];

                    if($datestring != ''){
                        $dates = explode(' - ', $datestring);

                        //print_r($dates);

                        if(count($dates) == 2){
                            //$daystart = date('Y-m-d H:i:s',strtotime($dates[0]));
                            //$dayend = date('Y-m-d H:i:s',strtotime($dates[1]));

                            if(stripos($dates[0], ':' ) === false){
                                $datestart = $dates[0].' 00:00:00';
                            }else{
                                $datestart = $dates[0];
                            }

                            if(stripos($dates[1], ':' ) === false){
                                $dateend = $dates[1].' 23:59:59';
                            }else{
                                $dateend = $dates[1];
                            }

                            $daystart = new MongoDate( strtotime($datestart) );
                            $dayend = new MongoDate( strtotime($dateend) );

                            //$qval = array($field =>array('$gte'=>$daystart,'$lte'=>$dayend));

                            $qval = array('$gte'=>$daystart,'$lte'=>$dayend);
                            //$qval = Request::input('sSearch_'.$idx);

                            //$model = $model->whereBetween($field,array($daystart,$dayend));

                            $model = $model->where(function($q) use($infilter,$field,$daystart,$dayend){
                                $q->whereBetween($field,array($daystart,$dayend));
                            });

                            $q[$field] = $qval;
                        }

                    }

                }


            }

        }

        //print_r($inputarray);

        return array('model'=>$model, 'q'=>$q, 'in'=>$inputarray );
    }

    public function SQLDLcompileSearch($fields,$model,$infilter){

        $q = array();
        $inputarray = array();

        for($i = 0;$i < count($fields);$i++){
            $idx = $i;

            $field = $fields[$i][0];
            $type = $fields[$i][1]['kind'];

            if(isset($infilter[$i])){
                $inputarray[$fields[$i][0]] = $infilter[$i];
            }

            //print $field."\r\n";

            $qval = '';

            $sfields = explode('.',$field);
            $sub = '';
            if(count($sfields) > 1){
                $sub = $sfields[0];
                $subfield = $sfields[1];
            }

            if( isset($infilter[$i]) && $infilter[$i] != '' )
            {

                //print $field.':'.$infilter[$i]."\r\n";
                //print $infilter[$i];

                $multi = (isset($fields[$i][1]['multi']))?$fields[$i][1]['multi']:false;
                $multirel = (isset($fields[$i][1]['multi']))?$fields[$i][1]['multirel']:'AND';

                if( $type == 'text'){
                    if($fields[$i][1]['query'] == 'like'){
                        $pos = $fields[$i][1]['pos'];
                        if($pos == 'both'){

                            $model = $model->where(function($q) use($infilter,$field,$multi,$multirel,$idx){

                                if($multi){
                                    $n = 0;
                                    foreach($multi as $mf){
                                        if($n == 0){
                                            $q = $q->where($mf,'like','%'.$infilter[$idx].'%');
                                        }else{
                                            if($multirel == 'OR'){
                                                $q = $q->orWhere($mf,'like','%'.$infilter[$idx].'%');
                                            }else{
                                                $q = $q->where($mf,'like','%'.$infilter[$idx].'%');
                                            }
                                        }
                                        $n++;
                                    }
                                }else{
                                    $q->where($field,'like','%'.$infilter[$idx].'%');
                                    $qval = new MongoRegex('/'.$infilter[$idx].'/i');
                                }

                            });

                        }else if($pos == 'before'){

                            $model = $model->where(function($q) use($infilter,$field,$multi,$multirel,$idx){

                                if($multi){
                                    $n = 0;
                                    foreach($multi as $mf){
                                        if($n == 0){
                                            $q = $q->where($mf,'like','%'.$infilter[$idx]);
                                        }else{
                                            if($multirel == 'OR'){
                                                $q = $q->orWhere($mf,'like','%'.$infilter[$idx]);
                                            }else{
                                                $q = $q->where($mf,'like','%'.$infilter[$idx]);
                                            }
                                        }
                                        $n++;
                                    }
                                }else{
                                    $q->where($field,'like','%'.$infilter[$idx]);
                                    $qval = new MongoRegex('/'.$infilter[$idx].'/i');
                                }

                            });


                        }else if($pos == 'after'){

                            $model = $model->where(function($q) use($infilter,$field,$multi,$multirel,$idx){

                                if($multi){
                                    $n = 0;
                                    foreach($multi as $mf){
                                        if($n == 0){
                                            $q = $q->where($mf,'like',$infilter[$idx].'%');
                                        }else{
                                            if($multirel == 'OR'){
                                                $q = $q->orWhere($mf,'like',$infilter[$idx].'%');
                                            }else{
                                                $q = $q->where($mf,'like',$infilter[$idx].'%');
                                            }
                                        }
                                        $n++;
                                    }
                                }else{
                                    $q->where($field,'like',$infilter[$idx].'%');
                                    $qval = new MongoRegex('/'.$infilter[$idx].'/i');
                                }

                            });

                        }
                    }else{

                        $model = $model->where(function($q) use($infilter,$field,$multi,$multirel,$idx){

                            if($multi){
                                $n = 0;
                                foreach($multi as $mf){
                                    if($n == 0){
                                        $q = $q->where($mf,'=',$infilter[$idx]);
                                    }else{
                                        if($multirel == 'OR'){
                                            $q = $q->orWhere($mf,'=',$infilter[$idx]);
                                        }else{
                                            $q = $q->where($mf,'=',$infilter[$idx]);
                                        }
                                    }
                                    $n++;
                                }
                            }else{
                                $q->where($field,'=',$infilter[$idx]);
                            }

                        });


                    }

                    $q[$field] = $qval;

                }elseif($type == 'numeric' || $type == 'currency'){

                    $str = $infilter[$idx];

                    $sign = null;

                    $strval = trim(str_replace(array('<','>','='), '', $str));

                    $qval = (double)$strval;

                    if(strpos($str, "<=") !== false){
                        $sign = '<=';
                    }elseif(strpos($str, ">=") !== false){
                        $sign = '>=';
                    }elseif(strpos($str, ">") !== false){
                        $sign = '>';
                    }elseif(stripos($str, "<") !== false){
                        $sign = '<';
                    }else{
                        $sign = '=';
                    }


                    $model = $model->where(function($q) use($infilter,$field,$qval,$sign,$multi,$multirel,$idx){

                        if($multi){
                            $n = 0;
                            foreach($multi as $mf){
                                if($n == 0){
                                    $q = $q->where($mf,$sign,$qval);
                                }else{
                                    if($multirel == 'OR'){
                                        $q = $q->orWhere($mf,$sign,$qval);
                                    }else{
                                        $q = $q->where($mf,$sign,$qval);
                                    }
                                }
                                $n++;
                            }
                        }else{
                            $q->where($field,$sign,$qval);
                        }

                    });



                }elseif($type == 'date'|| $type == 'datetime'){
                    $datestring = $infilter[$idx];
                    $datestring = date('d-m-Y', $datestring / 1000);

                    if (($timestamp = $datestring) === false) {

                    } else {
                        //$daystart = new MongoDate(strtotime($datestring.' 00:00:00'));
                        //$dayend = new MongoDate(strtotime($datestring.' 23:59:59'));

                        $daystart = $datestring.' 00:00:00';
                        $dayend = $datestring.' 23:59:59';

                        //$qval = array($field =>array('$gte'=>$daystart,'$lte'=>$dayend));
                        //echo "$str == " . date('l dS \o\f F Y h:i:s A', $timestamp);

                        $model = $model->where(function($q) use($infilter,$field,$daystart,$dayend){
                            $q->whereBetween($field,array($daystart,$dayend));
                        });

                    }
                    $qval = array('$gte'=>$daystart,'$lte'=>$dayend);
                    //$qval = $infilter[$idx];

                    $q[$field] = $qval;
                }elseif($type == 'daterange'){
                    $datestring = $infilter[$idx];

                    //print $datestring;

                    if($datestring != ''){
                        $dates = explode(' - ', $datestring);

                        if(count($dates) == 2){

                            $daystart = date('Y-m-d',strtotime($dates[0])).' 00:00:00';
                            $dayend = date('Y-m-d',strtotime($dates[1])).' 23:59:59';

                            //$daystart = new MongoDate( strtotime($dates[0].' 00:00:00') );
                            //$dayend = new MongoDate( strtotime($dates[1].' 23:59:59') );

                            //print $daystart;
                            //$qval = array($field =>array('$gte'=>$daystart,'$lte'=>$dayend));

                            $qval = array('$gte'=>$daystart,'$lte'=>$dayend);
                            //$qval = $infilter[$idx];

                            $q[$field] = $qval;


                            $model = $model->where(function($q) use($infilter,$field,$daystart,$dayend){
                                $q->whereBetween($field,array($daystart,$dayend));
                            });


                        }

                    }

                }elseif($type == 'datetimerange'){
                    $datestring = $infilter[$idx];

                    if($datestring != ''){
                        $dates = explode(' - ', $datestring);

                        //print_r($dates);

                        if(count($dates) == 2){
                            //$daystart = date('Y-m-d H:i:s',strtotime($dates[0]));
                            //$dayend = date('Y-m-d H:i:s',strtotime($dates[1]));

                            if(stripos($dates[0], ':' ) === false){
                                $datestart = $dates[0].' 00:00:00';
                            }else{
                                $datestart = $dates[0];
                            }

                            if(stripos($dates[1], ':' ) === false){
                                $dateend = $dates[1].' 23:59:59';
                            }else{
                                $dateend = $dates[1];
                            }

                            //$daystart = new MongoDate( strtotime($datestart) );
                            //$dayend = new MongoDate( strtotime($dateend) );

                            //$qval = array($field =>array('$gte'=>$daystart,'$lte'=>$dayend));

                            $qval = array('$gte'=>$daystart,'$lte'=>$dayend);
                            //$qval = Request::input('sSearch_'.$idx);

                            //$model = $model->whereBetween($field,array($daystart,$dayend));

                            $model = $model->where(function($q) use($infilter,$field,$daystart,$dayend){
                                $q->whereBetween($field,array($daystart,$dayend));
                            });

                            $q[$field] = $qval;
                        }

                    }

                }


            }

        }

        //print_r($inputarray);

        return array('model'=>$model, 'q'=>$q, 'in'=>$inputarray );
    }

    public function MongoDLcompileSearch($fields,$model,$infilter){

        $q = array();

        //print_r($infilter);

        for($i = 1;$i < count($fields);$i++){
            $idx = $i;

            //print_r($fields[$i]);

            $field = $fields[$i][0];
            $type = $fields[$i][1]['kind'];


            $qval = '';

            $sfields = explode('.',$field);
            $sub = '';
            if(count($sfields) > 1){
                $sub = $sfields[0];
                $subfield = $sfields[1];
            }

            if(isset($infilter[$i]))
            {
                //print $infilter[$i];

                $multi = (isset($fields[$i][1]['multi']))?$fields[$i][1]['multi']:false;
                $multirel = (isset($fields[$i][1]['multi']))?$fields[$i][1]['multirel']:'AND';

                if( $type == 'text'){
                    if($fields[$i][1]['query'] == 'like'){
                        $pos = $fields[$i][1]['pos'];
                        if($pos == 'both'){

                            $model = $model->where(function($q) use($infilter,$field,$multi,$multirel,$idx){

                                if($multi){
                                    $n = 0;
                                    foreach($multi as $mf){
                                        if($n == 0){
                                            $q = $q->where($mf,'like','%'.$infilter[$idx].'%');
                                        }else{
                                            if($multirel == 'OR'){
                                                $q = $q->orWhere($mf,'like','%'.$infilter[$idx].'%');
                                            }else{
                                                $q = $q->where($mf,'like','%'.$infilter[$idx].'%');
                                            }
                                        }
                                        $n++;
                                    }
                                }else{
                                    $q->where($field,'like','%'.$infilter[$idx].'%');
                                    $qval = new MongoRegex('/'.$infilter[$idx].'/i');
                                }

                            });

                        }else if($pos == 'before'){

                            $model = $model->where(function($q) use($infilter,$field,$multi,$multirel,$idx){

                                if($multi){
                                    $n = 0;
                                    foreach($multi as $mf){
                                        if($n == 0){
                                            $q = $q->where($mf,'like','%'.$infilter[$idx]);
                                        }else{
                                            if($multirel == 'OR'){
                                                $q = $q->orWhere($mf,'like','%'.$infilter[$idx]);
                                            }else{
                                                $q = $q->where($mf,'like','%'.$infilter[$idx]);
                                            }
                                        }
                                        $n++;
                                    }
                                }else{
                                    $q->where($field,'like','%'.$infilter[$idx]);
                                    $qval = new MongoRegex('/'.$infilter[$idx].'/i');
                                }

                            });


                        }else if($pos == 'after'){

                            $model = $model->where(function($q) use($infilter,$field,$multi,$multirel,$idx){

                                if($multi){
                                    $n = 0;
                                    foreach($multi as $mf){
                                        if($n == 0){
                                            $q = $q->where($mf,'like',$infilter[$idx].'%');
                                        }else{
                                            if($multirel == 'OR'){
                                                $q = $q->orWhere($mf,'like',$infilter[$idx].'%');
                                            }else{
                                                $q = $q->where($mf,'like',$infilter[$idx].'%');
                                            }
                                        }
                                        $n++;
                                    }
                                }else{
                                    $q->where($field,'like',$infilter[$idx].'%');
                                    $qval = new MongoRegex('/'.$infilter[$idx].'/i');
                                }

                            });

                        }
                    }else{

                        $model = $model->where(function($q) use($infilter,$field,$multi,$multirel,$idx){

                            if($multi){
                                $n = 0;
                                foreach($multi as $mf){
                                    if($n == 0){
                                        $q = $q->where($mf,'=',$infilter[$idx]);
                                    }else{
                                        if($multirel == 'OR'){
                                            $q = $q->orWhere($mf,'=',$infilter[$idx]);
                                        }else{
                                            $q = $q->where($mf,'=',$infilter[$idx]);
                                        }
                                    }
                                    $n++;
                                }
                            }else{
                                $q->where($field,'=',$infilter[$idx]);
                                $qval = new MongoRegex('/'.$infilter[$idx].'/i');
                            }

                        });


                    }

                    $q[$field] = $qval;

                }elseif($type == 'numeric' || $type == 'currency'){

                    $str = $infilter[$idx];

                    $sign = null;

                    $strval = trim(str_replace(array('<','>','='), '', $str));

                    $qval = (double)$strval;

                    if(strpos($str, "<=") !== false){
                        $sign = '<=';
                    }elseif(strpos($str, ">=") !== false){
                        $sign = '>=';
                    }elseif(strpos($str, ">") !== false){
                        $sign = '>';
                    }elseif(stripos($str, "<") !== false){
                        $sign = '<';
                    }else{
                        $sign = '=';
                    }


                    $model = $model->where(function($q) use($infilter,$field,$qval,$sign,$multi,$multirel,$idx){

                        if($multi){
                            $n = 0;
                            foreach($multi as $mf){
                                if($n == 0){
                                    $q = $q->where($mf,$sign,$qval);
                                }else{
                                    if($multirel == 'OR'){
                                        $q = $q->orWhere($mf,$sign,$qval);
                                    }else{
                                        $q = $q->where($mf,$sign,$qval);
                                    }
                                }
                                $n++;
                            }
                        }else{
                            $q->where($field,$sign,$qval);
                        }

                    });



                }elseif($type == 'date'|| $type == 'datetime'){
                    $datestring = $infilter[$idx];
                    $datestring = date('d-m-Y', $datestring / 1000);

                    if (($timestamp = $datestring) === false) {

                    } else {
                        $daystart = new MongoDate(strtotime($datestring.' 00:00:00'));
                        $dayend = new MongoDate(strtotime($datestring.' 23:59:59'));

                        //$daystart = $datestring.' 00:00:00';
                        //$dayend = $datestring.' 23:59:59';

                        //$qval = array($field =>array('$gte'=>$daystart,'$lte'=>$dayend));
                        //echo "$str == " . date('l dS \o\f F Y h:i:s A', $timestamp);

                        $model = $model->where(function($q) use($infilter,$field,$daystart,$dayend){
                            $q->whereBetween($field,array($daystart,$dayend));
                        });

                    }
                    $qval = array('$gte'=>$daystart,'$lte'=>$dayend);
                    //$qval = $infilter[$idx];

                    $q[$field] = $qval;
                }elseif($type == 'daterange'){
                    $datestring = $infilter[$idx];

                    //print $datestring;

                    if($datestring != ''){
                        $dates = explode(' - ', $datestring);

                        if(count($dates) == 2){

                            //$daystart = date('Y-m-d',strtotime($dates[0])).' 00:00:00';
                            //$dayend = date('Y-m-d',strtotime($dates[1])).' 23:59:59';

                            $daystart = new MongoDate( strtotime($dates[0].' 00:00:00') );
                            $dayend = new MongoDate( strtotime($dates[1].' 23:59:59') );

                            //print $daystart;
                            //$qval = array($field =>array('$gte'=>$daystart,'$lte'=>$dayend));

                            $qval = array('$gte'=>$daystart,'$lte'=>$dayend);
                            //$qval = $infilter[$idx];

                            $q[$field] = $qval;


                            $model = $model->where(function($q) use($infilter,$field,$daystart,$dayend){
                                $q->whereBetween($field,array($daystart,$dayend));
                            });


                        }

                    }

                }elseif($type == 'datetimerange'){
                    $datestring = $infilter[$idx];

                    if($datestring != ''){
                        $dates = explode(' - ', $datestring);

                        //print_r($dates);

                        if(count($dates) == 2){
                            //$daystart = date('Y-m-d H:i:s',strtotime($dates[0]));
                            //$dayend = date('Y-m-d H:i:s',strtotime($dates[1]));

                            if(stripos($dates[0], ':' ) === false){
                                $datestart = $dates[0].' 00:00:00';
                            }else{
                                $datestart = $dates[0];
                            }

                            if(stripos($dates[1], ':' ) === false){
                                $dateend = $dates[1].' 23:59:59';
                            }else{
                                $dateend = $dates[1];
                            }

                            $daystart = new MongoDate( strtotime($datestart) );
                            $dayend = new MongoDate( strtotime($dateend) );

                            //$qval = array($field =>array('$gte'=>$daystart,'$lte'=>$dayend));

                            $qval = array('$gte'=>$daystart,'$lte'=>$dayend);
                            //$qval = Request::input('sSearch_'.$idx);

                            //$model = $model->whereBetween($field,array($daystart,$dayend));

                            $model = $model->where(function($q) use($infilter,$field,$daystart,$dayend){
                                $q->whereBetween($field,array($daystart,$dayend));
                            });

                            $q[$field] = $qval;
                        }

                    }

                }


            }

        }

        return array('model'=>$model, 'q'=>$q);
    }

	public function getAdd(){

		$controller_name = strtolower($this->controller_name);

        Former::framework($this->form_framework);

        $route = Route::current();
		//$this->crumb->add($controller_name.'/add','New '.str_singular($this->controller_name));
        $data = $this->beforeAddForm();

		$model = $this->model;

		$form = $this->form;

        $this->title = ($this->title == '')?str_singular($this->controller_name):str_singular($this->title);

        $this->crumb->addCrumb($this->title,url($route->getPrefix().'/'.$controller_name));

        $this->crumb->addCrumb('New '.$this->title,url('/'));

        $fupload = new Wupload();

        $backlink = ( $this->backlink == '' )?$route->getPrefix().'/'.$controller_name:$this->backlink  ;

		return View::make($controller_name.'.'.$this->form_add)
                    ->with('validator',$this->validator)
					->with('back',$backlink)
                    ->with('auxdata',$data)
					->with('form',$form)
                    ->with('fupload',$fupload)
					->with('submit',$route->getPrefix().'/'.$controller_name.'/add')
					->with('crumb',$this->crumb)
					->with('title','New '.$this->title);

	}

	public function postAdd($data = null){

        Former::setOption('fetch_errors', true);

		//print_r(Session::get('errors'));
		if(is_null($data)){
			$data = Request::input();
		}

		//print_r($data);

        $route = Route::current();

		$data = $this->beforeValidateAdd($data);

		$controller_name = strtolower($this->controller_name);

        $this->backlink = ($this->backlink == '')? $route->getPrefix().'/'.$controller_name:$this->backlink;

	    $validation = Validator::make($input = $data, $this->validator);

        $actor = (isset(Auth::user()->email))?Auth::user()->name.' - '.Auth::user()->email:'guest';

        $backlink = ( $this->backlink == '' )?$route->getPrefix().'/'.$controller_name:$this->backlink  ;

	    if($validation->fails()){

            Event::fire('log.a',array($controller_name, 'add' ,$actor,'validation failed'));

	    	return redirect($route->getPrefix().'/'.$controller_name.'/add')
                ->withErrors($validation)
                ->withInput(Request::all());

	    }else{

			unset($data['csrf_token']);

			$data['createdDate'] = new MongoDate();
			$data['lastUpdate'] = new MongoDate();

            $data['ownerId'] = Auth::user()->_id;
            $data['ownerName'] = Auth::user()->name;


            // process tags by default
            if(isset($data['tags'])){
                $tags = $this->tagToArray($data['tags']);
                $data['tagArray'] = $tags;
                $this->saveTags($tags);
            }

			$model = $this->model;

			$data = $this->beforeSave($data);


			if($obj = $model->insert($data)){

				$obj = $this->afterSave($data);

                Event::fire('log.a',array($controller_name, 'add' ,$actor,json_encode($obj)));
				//Event::fire('product.createformadmin',array($obj['_id'],$passwordRandom,$obj['conventionPaymentStatus']));
		    	return redirect($backlink)->with('notify_success',ucfirst(str_singular($controller_name)).' saved successfully');
			}else{

                Event::fire('log.a',array($controller_name, 'add' ,$actor,'saving failed'));

    	    	return redirect($backlink)->with('notify_success',ucfirst(str_singular($controller_name)).' saving failed');
			}

	    }

	}

	public function getEdit($id){

		$controller_name = strtolower($this->controller_name);

		//$this->crumb->add(strtolower($this->controller_name).'/edit','Edit',false);

		//$model = $this->model;
        if($this->model instanceOf Jenssegers\Mongodb\Model ){
            $_id = new MongoId($id);
        }else{

        }

        $route = Route::current();

		//$population = $model->where('_id',$_id)->first();

        $population = $this->model->find($id)->toArray();

		$population = $this->beforeUpdateForm($population);

		foreach ($population as $key=>$val) {
			if($val instanceof MongoDate){
				$population[$key] = date('d-m-Y H:i:s',$val->sec);
			}
		}

        if($this->model instanceOf Jenssegers\Mongodb\Model ){

        }else{
            $population['_id'] = $id;
        }

		//print_r($population);

		//exit();

		Former::populate($population);

        $this->title = ($this->title == '')?str_singular($this->controller_name):str_singular($this->title);

        $this->crumb->addCrumb($this->title,url($route->getPrefix().'/'.$controller_name));

        $this->crumb->addCrumb('Update '.$this->title,url('/'));

        $fupload = new Wupload();

		return View::make(strtolower($this->controller_name).'.'.$this->form_edit)
					->with('back',$route->getPrefix().'/'.$controller_name)
                    ->with('crumb',$this->crumb)
                    ->with('fupload',$fupload)
					->with('formdata',$population)
					->with('submit',$route->getPrefix().'/'.strtolower($this->controller_name).'/edit/'.$id)
					->with('title','Edit '.$this->title);
	}


	public function postEdit($_id,$data = null){

		$controller_name = strtolower($this->controller_name);
		//print_r(Session::get('permission'));

        $route = Route::current();

        $backlink = ($this->backlink == '')?$route->getPrefix().'/'.$controller_name:$this->backlink;

	    $validation = Validator::make($input = Request::all(), $this->validator);

        $actor = (isset(Auth::user()->email))?Auth::user()->name.' - '.Auth::user()->email:'guest';

	    if($validation->fails()){
            $messages = $validation->messages();

            Event::fire('log.a',array($controller_name, 'update' ,$actor,'validation failed'));

	    	return redirect($route->getPrefix().'/'.$controller_name.'/edit/'.$_id)
                        ->withInput(Request::all())
                        ->withErrors($validation);

	    }else{


	    	if(is_null($data)){
				$data = Request::input();
	    	}

            $model = $this->model;

            if(get_parent_class($model) == 'Jenssegers\Mongodb\Eloquent\Model' ){
                $id = new MongoId($_id);
                $data['lastUpdate'] = new MongoDate();
            }else{
                $id = $_id;
                $data['lastUpdate'] = date('Y-m-d H:i:s',time());
            }

			unset($data['csrf_token']);
			unset($data['_id']);

            if(isset($data['tags'])){
                $tags = $this->tagToArray($data['tags']);
                $data['tagArray'] = $tags;
                $this->saveTags($tags);
            }


			$data = $this->beforeUpdate($id,$data);

            if(get_parent_class($model) == 'Jenssegers\Mongodb\Eloquent\Model' ){
                //$obj = $model->where('_id',$id)->update($data);
                print_r($id);
                $obj = $model->where('_id','=',$id)->first();
            }else{
                $obj = $model->where('id',$id)->first();
            }

            foreach ($data as $key =>$value) {

                $obj->{$key} = $value;
                # code...
            }

            $obj = $obj->save();

			if($obj){

				$obj = $this->afterUpdate($id,$data);
				if($obj != false){

                    Event::fire('log.a',array($controller_name, 'update' ,$actor,json_encode($obj)));

			    	return redirect($backlink)->with('notify_success',ucfirst(str_singular($controller_name)).' saved successfully');
				}
			}else{

                Event::fire('log.a',array($controller_name, 'update' ,$actor,'saving failed'));

		    	return redirect($backlink)->with('notify_success',ucfirst(str_singular($controller_name)).' saving failed');
			}

	    }

	}



	public function postDel(){
		$id = Request::input('id');

		$controller_name = strtolower($this->controller_name);

		$model = $this->model;

		if(is_null($id)){
			$result = array('status'=>'ERR','data'=>'NOID');
		}else{


            if($this->model instanceOf Jenssegers\Mongodb\Model ){
                $id = new MongoId($id);
            }else{

            }

			if($model->where('_id',$id)->delete()){
				Event::fire($controller_name.'.delete',array('id'=>$id,'result'=>'OK'));
				$result = array('status'=>'OK','data'=>'CONTENTDELETED');
			}else{
				Event::fire($controller_name.'.delete',array('id'=>$id,'result'=>'FAILED'));
				$result = array('status'=>'ERR','data'=>'DELETEFAILED');
			}
		}

		return Response::json($result);
	}

    public function postToggle(){
        $id = Request::input('id');

        $controller_name = strtolower($this->controller_name);

        $model = $this->model;

        if(is_null($id)){
            $result = array('status'=>'ERR','data'=>'NOID');
        }else{

            $obj = $model->find($id);

            if(isset($obj->active) && $obj->active == true){
                $obj->active = false;
            }else{
                $obj->active = true;
            }

            if($obj->save()){
                Event::fire($controller_name.'.toggle',array('id'=>$id, 'active'=>$obj->active ,'result'=>'OK'));
                $result = array('status'=>'OK','data'=>'CONTENTTOGGLED');
            }else{
                Event::fire($controller_name.'.toggle',array('id'=>$id,'result'=>'FAILED'));
                $result = array('status'=>'ERR','data'=>'TOGGLEFAILED');
            }
        }

        return Response::json($result);
    }

	public function beforeSave($data)
	{
		return $data;
	}

	public function afterSave($data)
	{
		return $data;
	}

	public function makeActions($data){

        if(isset($data['_id']) && $data['_id'] instanceOf MongoId){
            $id = $data['_id'];
        }else{
            $id = (isset($data['id']))?$data['id']:'0';
        }

        $route = Route::current();

        $printslip = '<span class="printslip action" type"button" data-rel="tooltip" data-toggle="tooltip" data-placement="left" title="" data-original-title="Print Slip" id="'.$id.'" ><i class="fa fa-print"></i> Print Slip</span>';

        $detailview = '<span class="detailview action" type"button" data-rel="tooltip" data-toggle="tooltip" data-placement="left" title="" data-original-title="Order Detail" id="'.$id.'" ><i class="fa fa-eye"></i> View Order</span>';

        $delete = '<span class="del" type"button" data-rel="tooltip" data-toggle="tooltip" data-placement="left" title="" data-original-title="Delete item" id="'.$id.'" ><i class="fa fa-trash"></i> Del</span>';

        $edit = '<a href="'.url( $route->getPrefix().'/'.strtolower($this->controller_name).'/edit/'.$id).'" type"button" data-rel="tooltip" data-toggle="tooltip" data-placement="left" title="" data-original-title="Edit item" ><i class="fa fa-edit"></i> Edit</a>';
        $actions = $edit.'<br />'.$delete;

        //$actions = $printslip.'<br />'.$detailview;

		return $actions;
	}

	public function beforeUpdate($id,$data)
	{
		return $data;
	}

	public function afterUpdate($id,$data = null)
	{
		return $id;
	}

	public function beforeView($data)
	{
		return $data;
	}

	public function beforeValidateAdd($data)
	{
		return $data;
	}

    public function beforeAddForm()
    {
        return null;
    }

	public function beforeUpdateForm($population)
	{
		if(isset($population['tags']) && is_array($population['tags']))
		{
			$population['tags'] = implode(',', $population['tags'] );
		}
		return $population;
	}

    public function postInfo(){
        $pid = Request::input('product_id');

        $p = $this->model->find($pid);

        if($p){
            return Response::json(array('result'=>'OK:FOUND', 'data'=>$p->toArray() ));
        }else{
            return Response::json(array('result'=>'ERR:NOTFOUND'));
        }
    }

    public function rows_post_process($rows, $aux = null){
        return $rows;
    }

    public function SQL_before_paging($model){
        return $this->aux_data;
    }

    public function SQL_make_join($model){
        return $model;
    }

    public function SQL_additional_query($model){
        return $model;
    }

    public function column_count(){

        $count = count($this->fields);

        if($this->place_action == 'both' || $this->place_action == 'first'){
            $count++;
        }

        if($this->show_select == true){
            $count++;
        }

        $count++;

        return $count;
    }

    public function completeHeads($heads){

        $select_all = Former::checkbox()->name('Select All')->check(false)->id('select_all');

        //product head
        array_unshift($heads, array($select_all,array('search'=>false,'sort'=>false)));
        array_unshift($heads, array('#',array('search'=>false,'sort'=>false)));
        array_push($heads,
            array('Actions',array('search'=>false,'sort'=>false,'clear'=>true))
        );

        return $heads;
    }

	public function get_view($id){
		$_id = new MongoId($id);

		$model = $this->model;

		$obj = $model->where('_id',$_id)->get();

		$obj = $this->beforeView($obj);

		$this->crumb->add(strtolower($this->controller_name).'/view/'.$id,'View',false);
		$this->crumb->add(strtolower($this->controller_name).'/view/'.$id,$id,false);

		//return View::make(strtolower($this->controller_name).'.'.$this->view_object)
		return View::make('view')
			->with('obj',$obj);
	}

    public function postTabletoxls()
    {

        $fname =  $this->controller_name.'_'.date('d-m-Y-H-m-s',time());

        $sdata = $this->export_output_fields;

        $view = View::make('print.xls')->with('tables',$sdata['tables'])->render();

        $path = Excel::create( $fname, function($excel) use ($sdata){
                $excel->sheet('sheet1', function($sheet) use ($sdata){
                    //$sheet->fromArray($sdata);

                    //print_r($sdata);
                    $sheet->loadView('print.xls')->with('tables',$sdata['tables']);
                });
                    //->with($sdata);
            })->store('xls',public_path().'/storage/dled',true);


        file_put_contents(public_path().'/storage/dled/'.$fname.'.html', $view);
        //print_r($path);

        $fp = fopen(public_path().'/storage/dled/'.$fname.'.csv', 'w');

        foreach ($sdata as $fields) {
            fputcsv($fp, $fields, ',' , '"');
        }

        fclose($fp);


        $result = array(
            'status'=>'OK',
            'filename'=>$fname,
            'urlxls'=>url(strtolower($this->controller_name).'/dl/'.$path['file']),
            'urlcsv'=>url(strtolower($this->controller_name).'/csv/'.$fname.'.csv'),
            'q'=>''
        );

        print json_encode($result);

    }

    public function postReportdlxl()
    {


        $fname =  $this->controller_name.'_'.date('d-m-Y-H-m-s',time());

        if(!is_null($this->export_output_fields) && count($this->export_output_fields) > 0){
            $tempdata = array();
            $sfields = $sdata[1];
            foreach ($sdata as $sd) {
                $temprow = array();
                for($i = 0; $i < count($sd); $i++){
                    if( in_array($sfields[$i], $this->export_output_fields) ){
                        $temprow[] = $sd[$i];
                    }
                }
                $tempdata[] = $temprow;
            }

            $sdata = $tempdata;
        }

        $path = Excel::create( $fname, function($excel) use ($sdata){
                $excel->sheet('sheet1', function($sheet) use ($sdata){
                    $sheet->fromArray($sdata);
                });
                    //->with($sdata);
            })->store('xls',public_path().'/storage/dled',true);

        //print_r($path);

        $fp = fopen(public_path().'/storage/dled/'.$fname.'.csv', 'w');

        foreach ($sdata as $fields) {
            fputcsv($fp, $fields, ',' , '"');
        }

        fclose($fp);


        $result = array(
            'status'=>'OK',
            'filename'=>$fname,
            'urlxls'=>url(strtolower($this->controller_name).'/dl/'.$path['file']),
            'urlcsv'=>url(strtolower($this->controller_name).'/csv/'.$fname.'.csv'),
            'q'=>$lastQuery
        );

        print json_encode($result);

    }

    public function postDlxl()
    {
        set_time_limit(0);

        $fields = $this->fields; // fields set must align with search column index

        $search_fields = (is_null($this->search_fields))?$this->fields:$this->search_fields;

        if(is_null($this->heads)){
            $titles = array();
            foreach ($this->fields as $fh) {

                $alias = (isset($fh[1]['alias']))?$fh[1]['alias']:false;
                $titles[] = array(ucwords($fh[0]),array('search'=>true,'sort'=>true, 'alias'=>$alias));
            }
        }else{
            $titles = $this->heads;
        }

        $infilters = Request::input('filter');
        $insorting = Request::input('sort');

        $defsort = 1;
        $defdir = -1;

        $idx = 0;
        $q = array();

        $hilite = array();
        $hilite_replace = array();

        $colheads = array();
        $coltitles = array();

        //exit();
        $model = $this->model;

        $model = $this->SQL_additional_query($model);

        array_shift($infilters);
        if($this->place_action == 'both' || $this->place_action == 'first'){
            array_shift($infilters);
        }

        $comres = $this->DLcompileSearch($search_fields, $model,$infilters);

        $model = $comres['model'];
        $q = $comres['q'];
        $searchpar = $comres['in'];

        if($insorting[0] == 0){
            $sort_col = $this->def_order_by;

            $sort_dir = $this->def_order_dir;
        }else{
            $sort_col = $fields[$insorting[0]][0];

            $sort_dir = $insorting[1];

        }

        //print $sort_col.' -> '.$sort_dir;

        $count_all = $model->count();
        //$count_display_all = $model->count();
        $count_display_all = $count_all;

        $this->aux_data = $this->SQL_before_paging($model);

        $results = $model->orderBy($sort_col, $sort_dir )->get();

        $lastQuery = $q;

        //print_r($results->toArray());

        $aadata = array();

        $counter = 1;

        //print_r($titles);



        //print count($fields)."\r\n";
        //print count($titles);

        for($i = 0;$i < count($titles);$i++){
            $idx = $i;


            $field = $fields[$i][0];

            if( isset($titles[$i][1]['alias']) && $titles[$i][1]['alias']){
                $title = $titles[$i][1]['alias'];
                $field = $titles[$i][1]['alias'];
            }else{
                $title = $titles[$i][0];
            }

            //print $field;

            $colheads[$i] = $field;
            $coltitles[$i] = ucwords( str_replace('_', ' ', $title) );
        }

        //die();

        foreach ($results->toArray() as $doc) {

            $row = array();

            //print_r($results);

            $tdoc = array();
            foreach($doc as $k=>$v){
                $tdoc[$k]= $v;
            }

            $doc = $tdoc;
            //$row[] = $counter;

            foreach($fields as $field){
                if($field[1]['kind'] != false && ( isset($field[1]['show']) && $field[1]['show'] == true ) ){
                    /*
                    $fieldarray = explode('.',$field[0]);
                    if(is_array($fieldarray) && count($fieldarray) > 1){
                        $fieldarray = implode('\'][\'',$fieldarray);
                        $cstring = '$label = (isset($doc[\''.$fieldarray.'\']))?true:false;';
                        eval($cstring);
                    }else{
                    */
                        $label = (isset($doc[$field[0]]))?true:false;
                    //}

                    if($label){

                        try{

                            if( isset($field[1]['callback']) && $field[1]['callback'] != ''){
                                $callback = $field[1]['callback'];
                                $row[] = $this->$callback($doc, $field[0]);
                            }else{

                                $rowitem = '';

                                if($field[1]['kind'] == 'datetime' || $field[1]['kind'] == 'datetimerange'){
                                    if($doc[$field[0]] instanceof MongoDate){
                                        $rowitem = date('d-m-Y H:i:s',$doc[$field[0]]->sec);
                                    }elseif ($doc[$field[0]] instanceof Date) {
                                        $rowitem = date('d-m-Y H:i:s',$doc[$field[0]]);
                                    }else{
                                        //$rowitem = $doc[$field[0]];
                                        if(is_array($doc[$field[0]])){
                                            $rowitem = date('d-m-Y H:i:s', time() );
                                        }else{
                                            $rowitem = date('d-m-Y H:i:s',strtotime($doc[$field[0]]) );
                                        }
                                    }
                                }elseif($field[1]['kind'] == 'date' || $field[1]['kind'] == 'daterange'){
                                    if($doc[$field[0]] instanceof MongoDate){
                                        $rowitem = date('d-m-Y',$doc[$field[0]]->sec);
                                    }elseif ($doc[$field[0]] instanceof Date) {
                                        $rowitem = date('d-m-Y',$doc[$field[0]]);
                                    }else{
                                        //$rowitem = $doc[$field[0]];
                                        $rowitem = date('d-m-Y',strtotime($doc[$field[0]]) );
                                    }
                                }elseif($field[1]['kind'] == 'currency'){
                                    $num = $doc[$field[0]];

                                    if(is_null($num) || trim($num) == ''){
                                        $num = 0;
                                    }else{
                                        $num = doubleval($num);
                                    }

                                    $rowitem = number_format($num,2,',','.');
                                }else{
                                    $rowitem = $doc[$field[0]];
                                }
                                /*
                                if(isset($field[1]['attr'])){
                                    $attr = '';
                                    foreach ($field[1]['attr'] as $key => $value) {
                                        $attr .= $key.'="'.$value.'" ';
                                    }
                                    $row[] = '<span '.$attr.' >'.$rowitem.'</span>';
                                }else{
                                    $row[] = $rowitem;
                                }
                                */

                                $row[] = $rowitem;

                            }

                        }catch(Exception $e){

                            $row[] = '';

                        }

                    }else{
                        $row[] = '';
                    }
                }
            }

            $aadata[] = $row;

            $counter++;
        }

        $sdata = $aadata;

        //print_r($colheads);

        //print_r($sdata);


        //array_shift($colheads);
        //array_shift($coltitles);
        if($this->place_action == 'both' || $this->place_action == 'first'){
            //array_shift($colheads);
            //array_shift($coltitles);
        }


        array_unshift($sdata,$colheads);
        array_unshift($sdata,$coltitles);



        //print public_path();

        $fname =  $this->controller_name.'_'.date('d-m-Y-H-m-s',time());


        /*
        if(!is_null($this->export_output_fields) && count($this->export_output_fields) > 0){
            $tempdata = array();
            $sfields = $sdata[1];
            foreach ($sdata as $sd) {
                $temprow = array();
                for($i = 0; $i < count($sd); $i++){
                    if( in_array($sfields[$i], $this->export_output_fields) ){
                        $temprow[] = $sd[$i];
                    }
                }
                $tempdata[] = $temprow;
            }

            $sdata = $tempdata;
        }
        */

        /*
        Excel::create( $fname )
            ->sheet('sheet1')
            ->with($sdata)
            ->save('xls',public_path().'/storage/dled');

        Excel::create( $fname )
            ->sheet('sheet1')
            ->with($sdata)
            ->save('xls',public_path().'/storage/dled');
        */

        $path = Excel::create( $fname, function($excel) use ($sdata){
                $excel->sheet('sheet1', function($sheet) use ($sdata){
                    $sheet->fromArray($sdata);
                });
                    //->with($sdata);
            })->store('xls',public_path().'/storage/dled',true);

        //print_r($path);

        $fp = fopen(public_path().'/storage/dled/'.$fname.'.csv', 'w');

        foreach ($sdata as $fields) {
            fputcsv($fp, $fields, ',' , '"');
        }

        fclose($fp);

        $route = Route::current();


        $result = array(
            'status'=>'OK',
            'filename'=>$fname,
            'urlxls'=>url($route->getPrefix().'/'.strtolower($this->controller_name).'/dl/'.$path['file']),
            'urlcsv'=>url($route->getPrefix().'/'.strtolower($this->controller_name).'/csv/'.$fname.'.csv'),
            'q'=>$lastQuery,
            'search'=>$searchpar
        );

        print json_encode($result);

    }

    public function postSQLDlxl()
    {
        set_time_limit(0);

        $fields = $this->fields; // fields set must align with search column index

        $search_fields = (is_null($this->search_fields))?$this->fields:$this->search_fields;

        if(is_null($this->heads)){
            $titles = array();
            foreach ($this->fields as $fh) {

                $alias = (isset($fh[1]['alias']))?$fh[1]['alias']:false;
                $titles[] = array(ucwords($fh[0]),array('search'=>true,'sort'=>true, 'alias'=>$alias));
            }
        }else{
            $titles = $this->heads;
        }

        $infilters = Request::input('filter');
        $insorting = Request::input('sort');

        $defsort = 1;
        $defdir = -1;

        $idx = 0;
        $q = array();

        $hilite = array();
        $hilite_replace = array();

        $colheads = array();
        $coltitles = array();

        //exit();
        $model = DB::connection($this->sql_connection)->table($this->sql_table_name);

        $model = $this->SQL_additional_query($model);

        array_shift($infilters);
        if($this->place_action == 'both' || $this->place_action == 'first'){
            array_shift($infilters);
        }

        $comres = $this->SQLDLcompileSearch($search_fields, $model,$infilters);

        $model = $comres['model'];
        $q = $comres['q'];
        $searchpar = $comres['in'];

        if($insorting[0] == 0){
            $sort_col = $this->def_order_by;

            $sort_dir = $this->def_order_dir;
        }else{
            $sort_col = $fields[$insorting[0]][0];

            $sort_dir = $insorting[1];

        }

        //print $sort_col.' -> '.$sort_dir;

        $count_all = $model->count();
        //$count_display_all = $model->count();
        $count_display_all = $count_all;

        $this->aux_data = $this->SQL_before_paging($model);

        $results = $model->orderBy($sort_col, $sort_dir )->get();

        $lastQuery = $q;

        //print_r($results->toArray());

        $aadata = array();

        $counter = 1;

        //print_r($titles);



        //print count($fields)."\r\n";
        //print count($titles);

        for($i = 0;$i < count($titles);$i++){
            $idx = $i;


            $field = $fields[$i][0];

            if( isset($titles[$i][1]['alias']) && $titles[$i][1]['alias']){
                $title = $titles[$i][1]['alias'];
                $field = $titles[$i][1]['alias'];
            }else{
                $title = $titles[$i][0];
            }

            //print $field;

            $colheads[$i] = $field;
            $coltitles[$i] = ucwords( str_replace('_', ' ', $title) );
        }

        //die();

        foreach ($results as $doc) {

            $row = array();

            //print_r($results);

            $tdoc = array();
            foreach($doc as $k=>$v){
                $tdoc[$k]= $v;
            }

            $doc = $tdoc;
            //$row[] = $counter;

            foreach($fields as $field){
                if($field[1]['kind'] != false && ( isset($field[1]['show']) && $field[1]['show'] == true ) ){

                    $fieldarray = explode('.',$field[0]);
                    if(is_array($fieldarray) && count($fieldarray) > 1){
                        $fieldarray = implode('\'][\'',$fieldarray);
                        $cstring = '$label = (isset($doc[\''.$fieldarray.'\']))?true:false;';
                        eval($cstring);
                    }else{
                        $label = (isset($doc[$field[0]]))?true:false;
                    }


                    if($label){

                        if( isset($field[1]['callback']) && $field[1]['callback'] != ''){
                            $callback = $field[1]['callback'];
                            $row[] = $this->$callback($doc, $field[0]);
                        }else{
                            if($field[1]['kind'] == 'datetime'){
                                if($doc[$field[0]] instanceof MongoDate){
                                    $rowitem = date('d-m-Y H:i:s',$doc[$field[0]]->sec);
                                }elseif ($doc[$field[0]] instanceof Date) {
                                    $rowitem = date('d-m-Y H:i:s',$doc[$field[0]]);
                                }else{
                                    //$rowitem = $doc[$field[0]];
                                    if(is_array($doc[$field[0]])){
                                        $rowitem = date('d-m-Y H:i:s', time() );
                                    }else{
                                        $rowitem = date('d-m-Y H:i:s',strtotime($doc[$field[0]]) );
                                    }
                                }
                            }elseif($field[1]['kind'] == 'date'){
                                if($doc[$field[0]] instanceof MongoDate){
                                    $rowitem = date('d-m-Y',$doc[$field[0]]->sec);
                                }elseif ($doc[$field[0]] instanceof Date) {
                                    $rowitem = date('d-m-Y',$doc[$field[0]]);
                                }else{
                                    //$rowitem = $doc[$field[0]];
                                    $rowitem = date('d-m-Y',strtotime($doc[$field[0]]) );
                                }
                            }elseif($field[1]['kind'] == 'currency'){
                                $num = (double) $doc[$field[0]];
                                $rowitem = number_format($num,2,',','.');
                            }else{
                                $rowitem = $doc[$field[0]];
                            }

                            if(isset($field[1]['attr'])){
                                $attr = '';
                                foreach ($field[1]['attr'] as $key => $value) {
                                    $attr .= $key.'="'.$value.'" ';
                                }
                                $row[] = '<span '.$attr.' >'.$rowitem.'</span>';
                            }else{
                                $row[] = $rowitem;
                            }

                        }


                    }else{
                        $row[] = '';
                    }
                }
            }

            $aadata[] = $row;

            $counter++;
        }

        $sdata = $aadata;

        array_shift($colheads);
        array_shift($colheads);
        array_shift($coltitles);
        array_shift($coltitles);

        array_unshift($sdata,$colheads);
        array_unshift($sdata,$coltitles);

        //print_r($sdata);
        //print public_path();

        $fname =  $this->controller_name.'_'.date('d-m-Y-H-m-s',time());

        /*
        Excel::create( $fname )
            ->sheet('sheet1')
            ->with($sdata)
            ->save('xls',public_path().'/storage/dled');

        Excel::create( $fname )
            ->sheet('sheet1')
            ->with($sdata)
            ->save('xls',public_path().'/storage/dled');
        */

        $path = Excel::create( $fname, function($excel) use ($sdata){
                $excel->sheet('sheet1', function($sheet) use ($sdata){
                    $sheet->fromArray($sdata);
                });
                    //->with($sdata);
            })->store('xls',public_path().'/storage/dled',true);

        //print_r($path);

        $fp = fopen(public_path().'/storage/dled/'.$fname.'.csv', 'w');

        foreach ($sdata as $fields) {
            fputcsv($fp, $fields, ',' , '"');
        }

        fclose($fp);


        $result = array(
            'status'=>'OK',
            'filename'=>$fname,
            'urlxls'=>url(strtolower($this->controller_name).'/dl/'.$path['file']),
            'urlcsv'=>url(strtolower($this->controller_name).'/csv/'.$fname.'.csv'),
            'q'=>$lastQuery,
            'search'=>$searchpar
        );

        print json_encode($result);

    }

    public function getDl($filename)
    {
        $dlfile = public_path().'/storage/dled/'.$filename;

        $headers = array(
                'Content-Type: application/vnd.ms-excel'
            );
        return Response::download($dlfile, $filename, $headers );
    }

    public function getCsv($filename)
    {
        $dlfile = public_path().'/storage/dled/'.$filename;

        $headers = array(
                'Content-Type: text/csv'
            );
        return Response::download($dlfile, $filename, $headers );
    }

    public function getImport()
    {
        $controller_name = strtolower($this->controller_name);

        $this->title = ($this->title == '')?str_plural($this->controller_name):str_plural($this->title);

        $this->crumb->addCrumb($this->title,url($controller_name));

        $this->crumb->addCrumb('Import '.$this->title,url('/'));

        $route = Route::current();

        return View::make($this->import_main_form)
            ->with('title',$this->title)
            ->with('aux_form',$this->import_aux_form)
            ->with('crumb',$this->crumb)
            //->with('input_name',$this->input_name)
            ->with('importkey', $this->importkey)
            ->with('back',$route->getPrefix().'/'.strtolower($this->controller_name))
            ->with('submit',$route->getPrefix().'/'.strtolower($this->controller_name).'/uploadimport');
    }

    public function postUploadimport()
    {
        set_time_limit(0);

        date_default_timezone_set('Asia/Jakarta');

        $file = Request::file('inputfile');

        $headindex = Request::input('headindex');

        $firstdata = Request::input('firstdata');

        $datalimit = Request::input('limitdata');


        $importkey = (!is_null($this->importkey))?Request::input('importkey'):$this->importkey;

        $aux_form_data = $this->processImportAuxForm();

        //$importkey = $this->importkey;


        $rstring = str_random(15);

        $destinationPath = realpath('storage/upload').'/'.$rstring;

        $filename = $file->getClientOriginalName();
        $filemime = $file->getMimeType();
        $filesize = $file->getSize();
        $extension =$file->getClientOriginalExtension(); //if you need extension of the file

        $filename = str_replace(config('kickstart.invalidchars'), '-', $filename);

        $uploadSuccess = $file->move($destinationPath, $filename);

        $fileitems = array();

        if($uploadSuccess){

            $xlsfile = realpath('storage/upload').'/'.$rstring.'/'.$filename;

            //$imp = Excel::load($xlsfile)->toArray();

            $imp = array();
            $ihead = array();
            $idata = array();

            Excel::load($xlsfile,function($reader) use (&$imp,&$ihead, &$idata, $datalimit, $headindex, $firstdata ){
                //$reader->formatDates(true, 'Y-m-d H:i:s');
                $reader->noHeading();
                $reader->formatDates(true);
                $reader->setDateFormat('Y-m-d');
                $imp = $reader->skip($firstdata - 1)->toArray();
                $ihead = $reader->skip($headindex - 1)->take(1)->toArray();
                $idata = $reader->skip(config('import.data_row'))->take($datalimit)->toArray();

            })->get();

            $headrow = $ihead[0];

            $htemp = [];

            for($h=0;$h < count($headrow);$h++){
                if(isset($headrow[$h]) && trim($headrow[$h]) != '' ){
                    //$headrow[$h] = strtolower($headrow[$h]);
                    $htemp[] = $headrow[$h];
                }
            }

            if(count($aux_form_data) > 0){
                foreach($aux_form_data as $ak=>$av){
                    $headrow[] = strtolower($ak);
                }
            }

            //$headrow = $htemp;

            $firstdata = 0;

            $imported = array();

            $sessobj = new Importsession();

            $sessobj->heads = array_values($headrow);
            $sessobj->isHead = 1;
            $sessobj->sessId = $rstring;
            $sessobj->save();

            print "head";
            //print_r($ihead);
            print_r($headrow);
            //print_r($imp);

            for($i = $firstdata; $i < count($imp);$i++){


                $check = '';

                $rowitem = $imp[$i];

                $imported[] = $rowitem;

                $sessobj = new Importsession();

                $rowtemp = array();

                //print "item before clean up";
                //print_r($rowitem);

                foreach($rowitem as $k=>$v){

                    if( isset($headrow[$k]) && trim($headrow[$k]) != ''){
                        $hkey = $headrow[$k];
                        $v = trim($v);
                        $sessobj->{ $hkey } = $this->prepImportItem($headrow[$k],$v,$rowitem);
                        $rowtemp[$hkey] = $v;
                        $check .= $v;
                    }

                }

                if(count($aux_form_data) > 0){
                    foreach($aux_form_data as $ak=>$av){

                        if(trim($ak) != ''){
                            $sessobj->{ $ak } = $this->prepImportItem($ak,$av);
                            $rowtemp[$ak] = $av;
                        }
                    }
                }

                $rowitem = $rowtemp;


                $sessobj->sessId = $rstring;
                $sessobj->isHead = 0;

                print "object to save";
                print_r($sessobj->toArray());

                if(trim($check) == ''){

                }else{
                    $sessobj->save();
                }

            }

        }

        $route = Route::current();

        $this->backlink = $route->getPrefix().'/'.strtolower($this->controller_name);

        $commit_url = $this->backlink.'/commit/'.$rstring;

        return redirect($commit_url);

    }

    public function processImportAuxForm(){
        return array();
    }

    public function prepImportItem($field, $val){
        return $val;
    }

    public function getCommit($sessid)
    {
        $route = Route::current();

        $heads = Importsession::where('sessId','=',$sessid)
            ->where('isHead','=',1)
            ->first();

        $heads = $heads['heads'];

        $imports = Importsession::where('sessId','=',$sessid)
            ->where('isHead','=',0)
            ->take(200)
            ->skip(0)
            ->get();

        $headselect = array();

        foreach ($heads as $h) {
            $headselect[$h] = $h;
        }

        $title = $this->controller_name;

        $submit = $route->getPrefix().'/'.strtolower($this->controller_name).'/commit/'.$sessid;

        $controller_name = strtolower($this->controller_name);

        $this->title = ($this->title == '')?str_plural($this->controller_name):str_plural($this->title);

        $this->crumb->addCrumb($this->title,url($controller_name));

        $this->crumb->addCrumb('Import '.$this->title,url($controller_name.'/import'));

        $this->crumb->addCrumb('Preview',url($controller_name.'/import'));

        return View::make('shared.commitselect')
            ->with('crumb',$this->crumb)
            ->with('title',$title)
            ->with('submit',$submit)
            ->with('headselect',$headselect)
            ->with('heads',$heads)
            ->with('back', $route->getPrefix().'/'.$controller_name.'/import')
            ->with('imports',$imports);
    }

    public function postCommit($sessid)
    {
        $in = Request::input();

        $force_all = $in['force_all'];

        $importkey = $in['edit_key'];

        $route = Route::current();

        $edit_selector = isset($in['edit_selector'])?$in['edit_selector']:array();

        if($force_all == 1){
            $selectall = Importsession::where('sessId','=',$sessid)
                                        ->where('isHead','=',0)
                                        ->get()->toArray();

            $selector = array();
            foreach($selectall as $sel){
                $selector[] = $sel['_id'];
            }
        }else{
            $selector = $in['selector'];
        }

        foreach($selector as $selected){
            $rowitem = Importsession::find($selected)->toArray();

            $do_edit = in_array($selected, $edit_selector);

            if($importkey != '' && !is_null($importkey) && isset($rowitem[$importkey]) && $do_edit ){
                $obj = $this->model
                    ->where($importkey, 'exists', true)
                    ->where($importkey, '=', $rowitem[$importkey])->first();

                if($obj){

                    foreach($rowitem as $k=>$v){
                        if($v != '' && $k != '_id'){
                            $obj->{$k} = $v;
                        }
                    }

                    unset($obj->isHead);

                    $obj->lastUpdate = new MongoDate();

                    print "updated item";
                    print_r($obj->toArray());

                    $obj->save();
                }else{

                    unset($rowitem['_id']);
                    unset($rowitem['isHead']);
                    $rowitem['createdDate'] = new MongoDate();
                    $rowitem['lastUpdate'] = new MongoDate();

                    print "upserted item";
                    print_r($rowitem);

                    $rowitem = $this->beforeImportCommit($rowitem);

                    if($rowitem){
                        $this->model->insert($rowitem);
                    }
                }


            }else{

                unset($rowitem['_id']);
                $rowitem['createdDate'] = new MongoDate();
                $rowitem['lastUpdate'] = new MongoDate();

                print "new inserted item";
                print_r($rowitem);

                $rowitem = $this->beforeImportCommit($rowitem);

                if($rowitem){
                    $this->model->insert($rowitem);
                }

            }


        }


        $this->backlink = $route->getPrefix().'/'.strtolower($this->controller_name);

        return redirect($this->backlink);

    }

    public function traverseFields($fields)
    {
        $out = array();
        foreach ($fields as $f) {
            $out[$f[0]] = $f[1];
        }

        return $out;
    }

    public function tagToArray($tagstring)
    {
        return explode(',',$tagstring);
    }

    public function saveTags($tags)
    {
        foreach($tags as $tag){
            $tag = trim($tag);
            //Tag::insert(array('tag'=>$tag));
            DB::collection('tags')->where('tag', $tag)->update(array('tag'=>$tag), array('upsert' => true));
        }

        return true;
    }

    function get_last_query() {
        $queries = DB::getQueryLog();
        $sql = end($queries);

        if( ! empty($sql['bindings']))
        {
            $pdo = DB::getPdo();
            foreach($sql['bindings'] as $binding)
            {
                  $sql['query'] = preg_replace('/\?/', $pdo->quote($binding), $sql['query'], 1);
            }
        }

        return $sql['query'];
    }

    public function get_not_found_page($backlink = null)
    {
        if(is_null($backlink)){
            $backlink = strtolower($this->controller_name);
        }

        return View::make('shared.notfound')
            ->with('backlink',$backlink)
            ->with('title','Not Found');

    }

	public function get_action_sample(){
		\Laravel\CLI\Command::run(array('notify'));
	}

    public function missingMethod($param = array())
    {
        //print_r($param);
    }

}