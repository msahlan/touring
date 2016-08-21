<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use Former\Facades\Former;
use App\Helpers\Logger;
use App\Helpers\Wupload;
use App\Models\Importsession;
use App\Models\Uploaded;


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
use DB;
use HTML;
use Excel;
use Image;

class UploadController extends Controller {

    public function __construct()
    {

    }

    public function postIndex()
    {
        $files = Request::file('files');

        $file = $files[0];

        //print_r($file);

        //exit();

        $large_wm = public_path().'/wm/wm_lrg.png';
        $med_wm = public_path().'/wm/wm_med.png';
        $sm_wm = public_path().'/wm/wm_sm.png';

        $rstring = str_random(15);

        $destinationPath = realpath('storage/media').'/'.$rstring;

        $filename = $file->getClientOriginalName();
        $filemime = $file->getMimeType();
        $filesize = $file->getSize();
        $extension =$file->getClientOriginalExtension(); //if you need extension of the file

        $filename = str_replace(config('kickstart.invalidchars'), '-', $filename);

        $uploadSuccess = $file->move($destinationPath, $filename);


        $is_image = $this->isImage($filemime);
        $is_audio = $this->isAudio($filemime);
        $is_video = $this->isVideo($filemime);
        $is_pdf = $this->isPdf($filemime);

        if(!($is_image || $is_audio || $is_video || $is_pdf)){
            $is_doc = true;
        }else{
            $is_doc = false;
        }

        if($is_image){

            $ps = config('picture.sizes');

            $thumbnail = Image::make($destinationPath.'/'.$filename)
                ->fit($ps['thumbnail']['width'],$ps['thumbnail']['height'])
                //->insert($sm_wm,0,0, 'bottom-right')
                ->save($destinationPath.'/th_'.$filename);

            $medium = Image::make($destinationPath.'/'.$filename)
                ->fit($ps['medium']['width'],$ps['medium']['height'])
                //->insert($med_wm,0,0, 'bottom-right')
                ->save($destinationPath.'/med_'.$filename);

            $large = Image::make($destinationPath.'/'.$filename)
                ->fit($ps['large']['width'],$ps['large']['height'])
                //->insert($large_wm, 'bottom-right',15,15)
                ->save($destinationPath.'/lrg_'.$filename);

            $full = Image::make($destinationPath.'/'.$filename)
                ->insert($large_wm, 'bottom-right',15,15)
                ->save($destinationPath.'/full_'.$filename);

            $image_size_array = array(
                'thumbnail_url'=> url('storage/media/'.$rstring.'/'.$ps['thumbnail']['prefix'].$filename),
                'large_url'=> url('storage/media/'.$rstring.'/'.$ps['large']['prefix'].$filename),
                'medium_url'=> url('storage/media/'.$rstring.'/'.$ps['medium']['prefix'].$filename),
                'full_url'=> url('storage/media/'.$rstring.'/'.$ps['full']['prefix'].$filename),
            );

        }else{

            if($is_audio){
                $thumbnail_url = url('images/audio.png');
            }elseif($is_video){
                $thumbnail_url = url('images/video.png');
            }else{
                $thumbnail_url = url('images/media.png');
            }

            $image_size_array = array(
                'thumbnail_url'=> $thumbnail_url,
                'large_url'=> '',
                'medium_url'=> '',
                'full_url'=> ''
            );
        }


        $fileitems = array();

        if($uploadSuccess){
            $item = array(
                    'url'=> url('storage/media/'.$rstring.'/'.$filename),
                    'temp_dir'=> $destinationPath,
                    'file_id'=> $rstring,
                    'is_image'=>$is_image,
                    'is_audio'=>$is_audio,
                    'is_video'=>$is_video,
                    'is_pdf'=>$is_pdf,
                    'is_doc'=>$is_doc,
                    'name'=> $filename,
                    'type'=> $filemime,
                    'size'=> $filesize,
                    'delete_url'=> url('storage/media/'.$rstring.'/'.$filename),
                    'delete_type'=> 'DELETE'
                );

            foreach($image_size_array as $k=>$v){
                $item[$k] = $v;
            }

            $fileitems[] = $item;

        }

        return Response::JSON(array('status'=>'OK','message'=>'' ,'files'=>$fileitems) );
    }

    public function postAvatar()
    {
        $files = Request::file('files');

        $parent_id = Request::input('parid');

        $parent_class = Request::input('parclass');

        $singlefile = Request::input('singlefile');

        $ns = Request::input('ns');

        $file = $files[0];

        $rstring = str_random(15);

        $destinationPath = realpath('storage/avatar').'/'.$rstring;

        $filename = $file->getClientOriginalName();
        $filemime = $file->getMimeType();
        $filesize = $file->getSize();
        $extension =$file->getClientOriginalExtension(); //if you need extension of the file

        $filename = str_replace(config('kickstart.invalidchars'), '-', $filename);

        $uploadSuccess = $file->move($destinationPath, $filename);


        $is_image = $this->isImage($filemime);
        $is_audio = $this->isAudio($filemime);
        $is_video = $this->isVideo($filemime);
        $is_pdf = $this->isPdf($filemime);

        if(!($is_image || $is_audio || $is_video || $is_pdf)){
            $is_doc = true;
        }else{
            $is_doc = false;
        }

        if($is_image){

            $ps = config('picture.sizes');

            $thumbnail = Image::make($destinationPath.'/'.$filename)
                ->fit($ps['thumbnail']['width'],$ps['thumbnail']['height'])
                ->save($destinationPath.'/th_'.$filename);

            $square = Image::make($destinationPath.'/'.$filename)
                ->fit($ps['square']['width'],$ps['square']['height'])
                ->save($destinationPath.'/sq_'.$filename);

            $medium = Image::make($destinationPath.'/'.$filename)
                ->fit($ps['medium']['width'],$ps['medium']['height'])
                ->save($destinationPath.'/med_'.$filename);

            $large = Image::make($destinationPath.'/'.$filename)
                ->fit($ps['large']['width'],$ps['large']['height'])
                ->save($destinationPath.'/lrg_'.$filename);

            $full = Image::make($destinationPath.'/'.$filename)
                ->save($destinationPath.'/full_'.$filename);

            $image_size_array = array(
                'thumbnail_url'=> url('storage/avatar/'.$rstring.'/'.$ps['thumbnail']['prefix'].$filename),
                'square_url'=> url('storage/avatar/'.$rstring.'/'.$ps['square']['prefix'].$filename),
                'large_url'=> url('storage/avatar/'.$rstring.'/'.$ps['large']['prefix'].$filename),
                'medium_url'=> url('storage/avatar/'.$rstring.'/'.$ps['medium']['prefix'].$filename),
                'full_url'=> url('storage/avatar/'.$rstring.'/'.$ps['full']['prefix'].$filename),
            );

        }else{

            if($is_audio){
                $thumbnail_url = url('images/audio.png');
            }elseif($is_video){
                $thumbnail_url = url('images/video.png');
            }else{
                $thumbnail_url = url('images/media.png');
            }

            $image_size_array = array(
                'thumbnail_url'=> $thumbnail_url,
                'large_url'=> '',
                'medium_url'=> '',
                'full_url'=> ''
            );
        }


        $fileitems = array();

        if($uploadSuccess){
            $item = array(
                    'ns'=>$ns,
                    'parent_id'=> $parent_id,
                    'parent_class'=> $parent_class,
                    'url'=> url('storage/media/'.$rstring.'/'.$filename),
                    'temp_dir'=> $destinationPath,
                    'file_id'=> $rstring,
                    'is_image'=>$is_image,
                    'is_audio'=>$is_audio,
                    'is_video'=>$is_video,
                    'is_pdf'=>$is_pdf,
                    'is_doc'=>$is_doc,
                    'name'=> $filename,
                    'type'=> $filemime,
                    'size'=> $filesize,
                    'deleted'=>0,
                    'createdDate'=>new MongoDate(),
                    'lastUpdate'=>new MongoDate()
                );

            foreach($image_size_array as $k=>$v){
                $item[$k] = $v;
            }

            Uploaded::insert($item);

            $fileitems[] = $item;

            //$fileitems[] = $rstring.'/'.$filename;

        }

        $files = Uploaded::where('parent_id',$parent_id )
                    ->where('parent_class', $parent_class)
                    //->where('ns',$ns)
                    ->where('deleted',0)
                    ->orderBy('createdDate','desc');

        if($singlefile){
            $files = $files->take(1)->get();
        }else{
            $files = $files->get();
        }


        $prefix = $parent_class;

        $thumbs = '';

        if( count($files->toArray()) > 0){
            foreach ($files->toArray() as $fd) {
                //print_r($fd);

                if($prefix != ''){
                    $detailview = $prefix.'.wdetail';
                }else{
                    $detailview = 'wupload.detail';
                }

                //print $detailview;

                $thumb = View::make($detailview)
                                ->with('filedata',$fd)
                                ->render();

                $thumbs .= $thumb;

            }

        }

        $thumbs = base64_encode($thumbs);

        return Response::JSON(array('status'=>'OK','message'=>'' ,'file'=>$fileitems, 'thumbs'=>$thumbs ) );
    }

    public function postAvatarold()
    {
        $files = Request::file('files');

        $parent_id = Request::input('parid');

        $parent_class = Request::input('parclass');

        $ns = Request::input('ns');

        $file = $files[0];

        $rstring = str_random(15);

        $destinationPath = realpath('storage/avatar').'/'.$rstring;

        $filename = $file->getClientOriginalName();
        $filemime = $file->getMimeType();
        $filesize = $file->getSize();
        $extension =$file->getClientOriginalExtension(); //if you need extension of the file

        $filename = str_replace(config('kickstart.invalidchars'), '-', $filename);

        $uploadSuccess = $file->move($destinationPath, $filename);


        $is_image = $this->isImage($filemime);
        $is_audio = $this->isAudio($filemime);
        $is_video = $this->isVideo($filemime);
        $is_pdf = $this->isPdf($filemime);

        if(!($is_image || $is_audio || $is_video || $is_pdf)){
            $is_doc = true;
        }else{
            $is_doc = false;
        }

        if($is_image){

            $ps = config('picture.sizes');

            $thumbnail = Image::make($destinationPath.'/'.$filename)
                ->fit(50,50)
                //->fit($ps['thumbnail']['width'],$ps['thumbnail']['height'])
                ->save($destinationPath.'/th_'.$filename);

            $medium = Image::make($destinationPath.'/'.$filename)
                ->fit($ps['medium']['width'],$ps['medium']['height'])
                ->save($destinationPath.'/med_'.$filename);

            $large = Image::make($destinationPath.'/'.$filename)
                ->fit($ps['large']['width'],$ps['large']['height'])
                ->save($destinationPath.'/lrg_'.$filename);

            $full = Image::make($destinationPath.'/'.$filename)
                ->save($destinationPath.'/full_'.$filename);

            $image_size_array = array(
                'thumbnail_url'=> url('storage/avatar/'.$rstring.'/'.$ps['thumbnail']['prefix'].$filename),
                'large_url'=> url('storage/avatar/'.$rstring.'/'.$ps['large']['prefix'].$filename),
                'medium_url'=> url('storage/avatar/'.$rstring.'/'.$ps['medium']['prefix'].$filename),
                'full_url'=> url('storage/avatar/'.$rstring.'/'.$ps['full']['prefix'].$filename),
            );

        }else{

            if($is_audio){
                $thumbnail_url = url('images/audio.png');
            }elseif($is_video){
                $thumbnail_url = url('images/video.png');
            }else{
                $thumbnail_url = url('images/media.png');
            }

            $image_size_array = array(
                'thumbnail_url'=> $thumbnail_url,
                'large_url'=> '',
                'medium_url'=> '',
                'full_url'=> ''
            );
        }


        $fileitems = array();

        if($uploadSuccess){
            $item = array(
                    'ns'=>$ns,
                    'parent_id'=> $parent_id,
                    'parent_class'=> $parent_class,
                    'url'=> url('storage/media/'.$rstring.'/'.$filename),
                    'temp_dir'=> $destinationPath,
                    'file_id'=> $rstring,
                    'is_image'=>$is_image,
                    'is_audio'=>$is_audio,
                    'is_video'=>$is_video,
                    'is_pdf'=>$is_pdf,
                    'is_doc'=>$is_doc,
                    'name'=> $filename,
                    'type'=> $filemime,
                    'size'=> $filesize,
                    'deleted'=>0,
                    'createdDate'=>new MongoDate(),
                    'lastUpdate'=>new MongoDate()
                );

            foreach($image_size_array as $k=>$v){
                $item[$k] = $v;
            }

            Uploaded::insert($item);

            $fileitems[] = $item;

            //$fileitems[] = $rstring.'/'.$filename;

        }

        $files = Uploaded::where('parent_id',$parent_id )
                    ->where('parent_class', $parent_class)
                    ->where('ns',$ns)
                    ->where('deleted',0)
                    ->orderBy('createdDate','desc')
                    ->get();

        $prefix = $parent_class;

        $thumbs = '';

        if( count($files->toArray()) > 0){
            foreach ($files->toArray() as $fd) {
                //print_r($fd);

                if($prefix != ''){
                    $detailview = $prefix.'.wdetail';
                }else{
                    $detailview = 'wupload.detail';
                }

                $thumb = View::make($detailview)
                                ->with('filedata',$fd)
                                ->render();

                $thumbs .= $thumb;

            }

        }

        $thumbs = base64_encode($thumbs);

        return Response::JSON(array('status'=>'OK','message'=>'' ,'file'=>$fileitems, 'thumbs'=>$thumbs ) );
    }

    public function postAsset($ns = 'asset')
    {
        $files = Request::file('files');

        $file = $files[0];

        //print_r($file);

        //exit();

        $large_wm = public_path().'/wm/wm_lrg.png';
        $med_wm = public_path().'/wm/wm_med.png';
        $sm_wm = public_path().'/wm/wm_sm.png';

        $rstring = str_random(15);

        $destinationPath = realpath('storage/media').'/'.$rstring;

        $filename = $file->getClientOriginalName();
        $filemime = $file->getMimeType();
        $filesize = $file->getSize();
        $extension =$file->getClientOriginalExtension(); //if you need extension of the file

        $filename = str_replace(config('kickstart.invalidchars'), '-', $filename);

        $uploadSuccess = $file->move($destinationPath, $filename);


        $is_image = $this->isImage($filemime);
        $is_audio = $this->isAudio($filemime);
        $is_video = $this->isVideo($filemime);
        $is_pdf = $this->isPdf($filemime);

        if(!($is_image || $is_audio || $is_video || $is_pdf)){
            $is_doc = true;
        }else{
            $is_doc = false;
        }

        if($is_image){

            $ps = config('picture.sizes');

            $thumbnail = Image::make($destinationPath.'/'.$filename)
                ->fit($ps['thumbnail']['width'],$ps['thumbnail']['height'])
                //->insert($sm_wm,0,0, 'bottom-right')
                ->save($destinationPath.'/th_'.$filename);

            $medium = Image::make($destinationPath.'/'.$filename)
                ->fit($ps['medium']['width'],$ps['medium']['height'])
                //->insert($med_wm,0,0, 'bottom-right')
                ->save($destinationPath.'/med_'.$filename);

            $large = Image::make($destinationPath.'/'.$filename)
                ->fit($ps['large']['width'],$ps['large']['height'])
                //->insert($large_wm, 'bottom-right',15,15)
                ->save($destinationPath.'/lrg_'.$filename);

            $full = Image::make($destinationPath.'/'.$filename)
                //->insert($large_wm, 'bottom-right',15,15)
                ->save($destinationPath.'/full_'.$filename);

            $image_size_array = array(
                'thumbnail_url'=> url('storage/media/'.$rstring.'/'.$ps['thumbnail']['prefix'].$filename),
                'large_url'=> url('storage/media/'.$rstring.'/'.$ps['large']['prefix'].$filename),
                'medium_url'=> url('storage/media/'.$rstring.'/'.$ps['medium']['prefix'].$filename),
                'full_url'=> url('storage/media/'.$rstring.'/'.$ps['full']['prefix'].$filename),
            );

            $status = 'OK';
            $message = '';
        }else{

            $file_url = url('storage/media/'.$rstring.'/'.$filename);

            if($is_audio){
                $thumbnail_url = View::make('media.audio')->with('title',$filename)->with('artist','-')->with('source',$file_url);
            }elseif($is_video){
                $thumbnail_url = url('images/video.png');
            }else{
                $thumbnail_url = url('images/media.png');
            }

            $image_size_array = array(
                'thumbnail_url'=> $thumbnail_url,
                'large_url'=> '',
                'medium_url'=> '',
                'full_url'=> ''
            );

            $status = 'ERR';
            $message = 'Please upload picture file only';
        }


        $fileitems = array();

        if($uploadSuccess){
            $item = array(
                    'ns'=>$ns,
                    'role'=>'photo',
                    'url'=> url('storage/media/'.$rstring.'/'.$filename),
                    'temp_dir'=> $destinationPath,
                    'file_id'=> $rstring,
                    'is_image'=>$is_image,
                    'is_audio'=>$is_audio,
                    'is_video'=>$is_video,
                    'is_pdf'=>$is_pdf,
                    'is_doc'=>$is_doc,
                    'name'=> $filename,
                    'type'=> $filemime,
                    'size'=> $filesize,
                    'delete_url'=> url('storage/media/'.$rstring.'/'.$filename),
                    'delete_type'=> 'DELETE'
                );

            foreach($image_size_array as $k=>$v){
                $item[$k] = $v;
            }

            $fileitems[] = $item;

        }

        return Response::JSON(array('status'=>$status,'role'=>'photo' ,'message'=>$message ,'files'=>$fileitems) );
    }

    public function postCover()
    {
        $files = Request::file('files');

        $file = $files[0];

        //print_r($file);

        //exit();

        $large_wm = public_path().'/wm/wm_lrg.png';
        $med_wm = public_path().'/wm/wm_med.png';
        $sm_wm = public_path().'/wm/wm_sm.png';

        $rstring = str_random(15);

        $destinationPath = realpath('storage/media').'/'.$rstring;

        $filename = $file->getClientOriginalName();
        $filemime = $file->getMimeType();
        $filesize = $file->getSize();
        $extension =$file->getClientOriginalExtension(); //if you need extension of the file

        $filename = str_replace(config('kickstart.invalidchars'), '-', $filename);

        $uploadSuccess = $file->move($destinationPath, $filename);


        $is_image = $this->isImage($filemime);
        $is_audio = $this->isAudio($filemime);
        $is_video = $this->isVideo($filemime);
        $is_pdf = $this->isPdf($filemime);

        if(!($is_image || $is_audio || $is_video || $is_pdf)){
            $is_doc = true;
        }else{
            $is_doc = false;
        }

        if($is_image){

            $ps = config('picture.sizes');

            $thumbnail = Image::make($destinationPath.'/'.$filename)
                ->fit($ps['thumbnail']['width'],$ps['thumbnail']['height'])
                //->insert($sm_wm,0,0, 'bottom-right')
                ->save($destinationPath.'/th_'.$filename);

            $medium = Image::make($destinationPath.'/'.$filename)
                ->fit($ps['medium']['width'],$ps['medium']['height'])
                //->insert($med_wm,0,0, 'bottom-right')
                ->save($destinationPath.'/med_'.$filename);

            $large = Image::make($destinationPath.'/'.$filename)
                ->fit($ps['large']['width'],$ps['large']['height'])
                //->insert($large_wm, 'bottom-right',15,15)
                ->save($destinationPath.'/lrg_'.$filename);

            $full = Image::make($destinationPath.'/'.$filename)
                ->insert($large_wm, 'bottom-right',15,15)
                ->save($destinationPath.'/full_'.$filename);

            $image_size_array = array(
                'thumbnail_url'=> url('storage/media/'.$rstring.'/'.$ps['thumbnail']['prefix'].$filename),
                'large_url'=> url('storage/media/'.$rstring.'/'.$ps['large']['prefix'].$filename),
                'medium_url'=> url('storage/media/'.$rstring.'/'.$ps['medium']['prefix'].$filename),
                'full_url'=> url('storage/media/'.$rstring.'/'.$ps['full']['prefix'].$filename),
            );

            $status = 'OK';
            $message = '';
        }else{
            $file_url = url('storage/media/'.$rstring.'/'.$filename);
            if($is_audio){
                $thumbnail_url = View::make('media.audio')->with('title',$filename)->with('artist','-')->with('source',$file_url);
            }elseif($is_video){
                $thumbnail_url = url('images/video.png');
            }else{
                $thumbnail_url = url('images/media.png');
            }

            $image_size_array = array(
                'thumbnail_url'=> $thumbnail_url,
                'large_url'=> '',
                'medium_url'=> '',
                'full_url'=> ''
            );

            $status = 'ERR';
            $message = 'Please upload picture file for cover';
        }


        $fileitems = array();

        if($uploadSuccess){
            $item = array(
                    'url'=> url('storage/media/'.$rstring.'/'.$filename),
                    'temp_dir'=> $destinationPath,
                    'file_id'=> $rstring,
                    'is_image'=>$is_image,
                    'is_audio'=>$is_audio,
                    'is_video'=>$is_video,
                    'is_pdf'=>$is_pdf,
                    'is_doc'=>$is_doc,
                    'name'=> $filename,
                    'type'=> $filemime,
                    'size'=> $filesize,
                    'delete_url'=> url('storage/media/'.$rstring.'/'.$filename),
                    'delete_type'=> 'DELETE'
                );

            foreach($image_size_array as $k=>$v){
                $item[$k] = $v;
            }

            $fileitems[] = $item;

        }

        return Response::JSON(array('status'=>$status,'role'=>'cover' ,'message'=>$message ,'files'=>$fileitems) );
    }

    public function postMedia()
    {
        $files = Request::file('files');

        $file = $files[0];

        //print_r($file);

        //exit();

        $large_wm = public_path().'/wm/wm_lrg.png';
        $med_wm = public_path().'/wm/wm_med.png';
        $sm_wm = public_path().'/wm/wm_sm.png';

        $rstring = str_random(15);

        $destinationPath = realpath('storage/media').'/'.$rstring;

        $filename = $file->getClientOriginalName();
        $filemime = $file->getMimeType();
        $filesize = $file->getSize();
        $extension =$file->getClientOriginalExtension(); //if you need extension of the file

        $filename = str_replace(config('kickstart.invalidchars'), '-', $filename);

        $uploadSuccess = $file->move($destinationPath, $filename);


        $is_image = $this->isImage($filemime);
        $is_audio = $this->isAudio($filemime);
        $is_video = $this->isVideo($filemime);
        $is_pdf = $this->isPdf($filemime);

        if(!($is_image || $is_audio || $is_video || $is_pdf)){
            $is_doc = true;
        }else{
            $is_doc = false;
        }

        if($is_image){

            $ps = config('picture.sizes');

            $thumbnail = Image::make($destinationPath.'/'.$filename)
                ->fit($ps['thumbnail']['width'],$ps['thumbnail']['height'])
                //->insert($sm_wm,0,0, 'bottom-right')
                ->save($destinationPath.'/th_'.$filename);

            $medium = Image::make($destinationPath.'/'.$filename)
                ->fit($ps['medium']['width'],$ps['medium']['height'])
                //->insert($med_wm,0,0, 'bottom-right')
                ->save($destinationPath.'/med_'.$filename);

            $large = Image::make($destinationPath.'/'.$filename)
                ->fit($ps['large']['width'],$ps['large']['height'])
                //->insert($large_wm, 'bottom-right',15,15)
                ->save($destinationPath.'/lrg_'.$filename);

            $full = Image::make($destinationPath.'/'.$filename)
                ->insert($large_wm, 'bottom-right',15,15)
                ->save($destinationPath.'/full_'.$filename);

            $image_size_array = array(
                'thumbnail_url'=> url('storage/media/'.$rstring.'/'.$ps['thumbnail']['prefix'].$filename),
                'large_url'=> url('storage/media/'.$rstring.'/'.$ps['large']['prefix'].$filename),
                'medium_url'=> url('storage/media/'.$rstring.'/'.$ps['medium']['prefix'].$filename),
                'full_url'=> url('storage/media/'.$rstring.'/'.$ps['full']['prefix'].$filename),
            );

            $status = 'ERR';
            $message = 'Please upload music or movie file';

        }else{
            $file_url = url('storage/media/'.$rstring.'/'.$filename);
            if($is_audio){
                $thumbnail = View::make('media.audio')->with('title',$filename)->with('artist','-')->with('source',$file_url)->render();
                $thumbnail_url = url('images/audio.png');
            }elseif($is_video){
                $thumbnail = View::make('media.video')->with('type',$filemime)->with('source',$file_url)->render();
                $thumbnail_url = url('images/video.png');
            }else{
                $thumbnail =
                $thumbnail_url = url('images/media.png');
            }

            $image_size_array = array(
                'thumbnail'=> $thumbnail,
                'thumbnail_url'=> $thumbnail_url,
                'large_url'=> '',
                'medium_url'=> '',
                'full_url'=> ''
            );

            $status = 'OK';
            $message = '';
        }


        $fileitems = array();

        if($uploadSuccess){
            $item = array(
                    'url'=> url('storage/media/'.$rstring.'/'.$filename),
                    'temp_dir'=> $destinationPath,
                    'file_id'=> $rstring,
                    'is_image'=>$is_image,
                    'is_audio'=>$is_audio,
                    'is_video'=>$is_video,
                    'is_pdf'=>$is_pdf,
                    'is_doc'=>$is_doc,
                    'name'=> $filename,
                    'type'=> $filemime,
                    'size'=> $filesize,
                    'delete_url'=> url('storage/media/'.$rstring.'/'.$filename),
                    'delete_type'=> 'DELETE'
                );

            foreach($image_size_array as $k=>$v){
                $item[$k] = $v;
            }

            $fileitems[] = $item;

        }

        return Response::JSON(array('status'=>$status, 'role'=>'media' ,'message'=>$message ,'files'=>$fileitems) );
    }

    public function postSlide()
    {
        $files = Request::file('files');

        $file = $files[0];

        //print_r($file);

        //exit();

        $large_wm = public_path().'/wm/wm_lrg.png';
        $med_wm = public_path().'/wm/wm_med.png';
        $sm_wm = public_path().'/wm/wm_sm.png';

        $rstring = str_random(15);

        $destinationPath = realpath('storage/media').'/'.$rstring;

        $filename = $file->getClientOriginalName();
        $filemime = $file->getMimeType();
        $filesize = $file->getSize();
        $extension =$file->getClientOriginalExtension(); //if you need extension of the file

        $filename = str_replace(config('kickstart.invalidchars'), '-', $filename);

        $uploadSuccess = $file->move($destinationPath, $filename);

        $thumbnail = Image::make($destinationPath.'/'.$filename)
            ->fit(100,74)
            //->insert($sm_wm,0,0, 'bottom-right')
            ->save($destinationPath.'/th_'.$filename);

        $medium = Image::make($destinationPath.'/'.$filename)
            ->fit(640,480)
            //->insert($med_wm,0,0, 'bottom-right')
            ->save($destinationPath.'/med_'.$filename);

        $large = Image::make($destinationPath.'/'.$filename)
            ->fit(800,600)
            ->insert($large_wm, 'bottom-right',15,15)
            ->save($destinationPath.'/lrg_'.$filename);

        $full = Image::make($destinationPath.'/'.$filename)
            ->insert($large_wm, 'bottom-right',15,15)
            ->save($destinationPath.'/full_'.$filename);

        $fileitems = array();

        if($uploadSuccess){
            $fileitems[] = array(
                    'url'=> url('storage/media/'.$rstring.'/'.$filename),
                    'thumbnail_url'=> url('storage/media/'.$rstring.'/th_'.$filename),
                    'large_url'=> url('storage/media/'.$rstring.'/lrg_'.$filename),
                    'medium_url'=> url('storage/media/'.$rstring.'/med_'.$filename),
                    'full_url'=> url('storage/media/'.$rstring.'/full_'.$filename),
                    'temp_dir'=> $destinationPath,
                    'file_id'=> $rstring,
                    'name'=> $filename,
                    'type'=> $filemime,
                    'size'=> $filesize,
                    'delete_url'=> url('storage/media/'.$rstring.'/'.$filename),
                    'delete_type'=> 'DELETE'
                );

        }

        return Response::JSON(array('files'=>$fileitems) );
    }

    public function postMusic()
    {
        $files = Request::file('files');

        $file = $files[0];

        //print_r($file);

        //exit();

        $rstring = str_random(15);

        $destinationPath = realpath('storage/media').'/'.$rstring;

        $filename = $file->getClientOriginalName();
        $filemime = $file->getMimeType();
        $filesize = $file->getSize();
        $extension =$file->getClientOriginalExtension(); //if you need extension of the file

        $filename = str_replace(config('kickstart.invalidchars'), '-', $filename);

        $uploadSuccess = $file->move($destinationPath, $filename);

        /*
        $thumbnail = Image::make($destinationPath.'/'.$filename)
            ->fit(100,100)
            ->save($destinationPath.'/th_'.$filename);
        */

        $fileitems = array();

        if($uploadSuccess){
            $fileitems[] = array(
                    'url'=> url('storage/media/'.$rstring.'/'.$filename),
                    'thumbnail_url'=> url('storage/media/th_music.jpg'),
                    'temp_dir'=> $destinationPath,
                    'name'=> $filename,
                    'type'=> $filemime,
                    'size'=> $filesize,
                    'delete_url'=> url('storage/media/'.$rstring.'/'.$filename),
                    'delete_type'=> 'DELETE'
                );

        }

        return Response::JSON(array('files'=>$fileitems) );
    }


    public function postAdd()
    {
        $files = Request::file('files');

        $file = $files[0];

        //print_r($file);

        //exit();

        $rstring = str_random(8);

        $destinationPath = realpath('storage/temp').'/'.$rstring;

        $filename = $file->getClientOriginalName();
        $filemime = $file->getMimeType();
        $filesize = $file->getSize();
        $extension =$file->getClientOriginalExtension(); //if you need extension of the file

        $filename = str_replace(config('kickstart.invalidchars'), '-', $filename);

        $uploadSuccess = $file->move($destinationPath, $filename);

        $thumbnail = Image::make($destinationPath.'/'.$filename)
            ->fit(320,240)
            ->save($destinationPath.'/th_'.$filename);

        $fileitems = array();

        if($uploadSuccess){
            $fileitems[] = array(
                    'url'=> url('storage/temp/'.$rstring.'/'.$filename),
                    'thumbnail_url'=> url('storage/temp/'.$rstring.'/th_'.$filename),
                    'temp_dir'=> $destinationPath,
                    'name'=> $filename,
                    'type'=> $filemime,
                    'size'=> $filesize,
                    'delete_url'=> 'http://url.to/delete /file/',
                    'delete_type'=> 'DELETE'
                );

        }

        return Response::JSON(array('files'=>$fileitems) );
    }


    public function postEdit()
    {
        $files = Request::file('files');

        $file = $files[0];

        //print_r($file);

        //exit();

        $rstring = str_random(8);

        $destinationPath = realpath('storage/temp').'/'.$rstring;

        $filename = $file->getClientOriginalName();
        $filemime = $file->getMimeType();
        $filesize = $file->getSize();
        $extension =$file->getClientOriginalExtension(); //if you need extension of the file

        $filename = str_replace(config('kickstart.invalidchars'), '-', $filename);

        $uploadSuccess = $file->move($destinationPath, $filename);

        $thumbnail = Image::make($destinationPath.'/'.$filename)
            ->fit(320,240)
            ->save($destinationPath.'/th_'.$filename);

        $fileitems = array();

        if($uploadSuccess){
            $fileitems[] = array(
                    'url'=> url('storage/temp/'.$rstring.'/'.$filename),
                    'thumbnail_url'=> url('storage/temp/'.$rstring.'/th_'.$filename),
                    'temp_dir'=> $destinationPath,
                    'name'=> $filename,
                    'type'=> $filemime,
                    'size'=> $filesize,
                    'delete_url'=> 'http://url.to/delete /file/',
                    'delete_type'=> 'DELETE'
                );

        }

        return Response::JSON(array('files'=>$fileitems) );
    }

    public function postDelete($file_id)
    {


    }

    public function postUp(){

        $file = Request::file('file');

        $destinationPath = config('kickstart.storage').'/uploads/'.str_random(8);
        $filename = $file->getClientOriginalName();
        //$extension =$file->getClientOriginalExtension();
        $upload_success = Request::file('file')->move($destinationPath, $filename);

        if( $upload_success ) {
           return Response::json('success', 200);
        } else {
           return Response::json('error', 400);
        }

    }

    private function isAudio($mime){
        return preg_match('/^audio/',$mime);
    }

    private function isVideo($mime){
        return preg_match('/^video/',$mime);
    }

    private function isImage($mime){
        return preg_match('/^image/',$mime);
    }

    private function isPdf($mime){
        return preg_match('/pdf/',$mime);
    }

}
