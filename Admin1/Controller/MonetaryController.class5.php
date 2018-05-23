<?php 
namespace Admin\Controller;
use Admin\Controller\CommonController;
use \Org\Util\Data;

class MonetaryController extends CommonController{


	protected $model;
	public function _initialize()
	{
		parent::_initialize();
		$this->isUclient = "Uclient";
		$this->model = M("Uclient");
	}

	//列表查询条件
	public function conditions(){
		if(in_array('1001',$_SESSION['level'])){
		  $kun =  array('id'=>array('gt','0'));

		}else if(in_array('1002',$_SESSION['level'])){
		  
		  $id=M("rank")->where("name='{$_SESSION['rank_name']}'")->getField("id");
		  $ids=M("rank")->where("pid={$id}")->getField("id",true);
		  $ids[]=$id;
		  $u_id=M("admin")->where(array("level_id"=>array("in",$ids)))->getField("id",true);
		  $kun = array("salesman"=>array("in",$u_id));

		}else{
			$kun= array("salesman"=>$_SESSION['aid']);
		}

		return $kun;
	}
	//速分贷
	public function index(){
		$kun=$this->conditions();
		$where=array("type"=>1);
		//添加搜索条件
		$post = I('get.');
		$stime = strtotime($post['stime']." 00:00:00");
		$endtime = strtotime($post['endtime']." 24:00:00");
		if(IS_GET){
			if($post['stime'] && $post['endtime'] && $post['sou']){

				$aid=M("member")->alias("m")->join("admin a on a.member_id=m.id")->Field("a.id")->where(array("m.username"=>array("like","%{$post['sou']}%")))->select();
				foreach ($aid as $value) {
					$aids[]=$value["id"];
				}
				$uid=M("cash")->where(array("name"=>array("like","%{$post['sou']}%")))->getField("id",true);
				if(count($aids)){
					$where['Salesman']=array("in",$aids);
				}else{
					$where["id"]=array("in",$uid);
				}
				$where[]=array("starttime"=>array(array("gt",$stime),array('elt',$endtime)));
				// $where[]=$kun;
			  }elseif($post['stime'] && $post['endtime'] && $post['branch']){
			  	if($post['branch']==33){
			  		$adimds=array(65,66,127,128,136);
			  	}else{
			  		$adimds=M("admin")->where("branch_id={$post['branch']}")->getField("id",true);
			  	}
			  	
			  	$where['Salesman']=array("in",$adimds);
			  	$where[]=array("starttime"=>array(array("gt",$stime),array('elt',$endtime)));

			  }elseif($post['stime'] && $post['endtime']){
			  	$where[]=array("starttime"=>array(array("gt",$stime),array('elt',$endtime)));
				// $where[]=$kun;
			  }elseif($post['branch']){
			  	if($post['branch']==33){
			  		$adimds=array(65,66,127,128,136);
			  	}else{
			  		$adimds=M("admin")->where("branch_id={$post['branch']}")->getField("id",true);
			  	}
			  	$where['Salesman']=array("in",$adimds);	

			  }elseif($post['sou']){
				  	$aid=M("member")->alias("m")->join("admin a on a.member_id=m.id")->Field("a.id")->where(array("m.username"=>array("like","%{$post['sou']}%")))->select();
					foreach ($aid as $value) {
						$aids[]=$value["id"];
					}
					$uid=M("cash")->where(array("name"=>array("like","%{$post['sou']}%")))->getField("id",true);
					if(count($aids)){
							$where['Salesman']=array("in",$aids);
						}else{
							$where["id"]=array("in",$uid);
						}
					// $where[]=$kun;
			  }

		}
		//添加搜索条件结束
		
		$where[] = $kun;
		$data=M("Cash")->where($where)->order("id desc")->select();
		// echo M("Cash")->getlastsql();
		foreach ($data as $key => $val) {
			$arr=M("member")->alias("m")->join("admin a on a.member_id=m.id")->where(array("a.id"=>$data[$key]['salesman']))->Field('m.username')->select();
			$data[$key]['salesman']=$arr['0']['username'];

			//滞纳金
			$latefee=M("Cash")->Field('money,returnlaf')->find($data[$key]['id']);
			$arr=M("Cashdetails")->where("uid={$data[$key]['id']} and repayment=1")->order('id asc')->limit(1)->Field('id,endtime')->select();
			$stime=strtotime($arr[0]['endtime']);
			$etime=time();    
			if($stime>0){
				if($etime>$stime){
					$timediff = $etime-$stime;
					 $days = intval($timediff/86400);
				}else{
					 $days=0;
				}
			}else{
				 $days=0;
			}
			$znj=($days*$latefee['money']*0.003)-$latefee['returnlaf'];
			$datass['Latefee']=0;
			M("Cash")->where("id={$data[$key]['id']}")->save($datass);
			$dataznj['Latefee']=$znj;
			M("Cash")->where("id={$data[$key]['id']}")->save($dataznj);

			$data[$key]['latefee']=$znj;

			//应还利息跟未还利息
			$arr=M("Cashdetails")->where("uid={$data[$key]['id']}")->order('id asc')->Field('id,endtime,returninterest,nowinterest')->select();
			// var_dump($arrs[1]);
			$time=date("Y-m-d",time());
			$times=explode('-', $time);
			// var_dump($times[1]);
			foreach ($arr as $k => $v) {
				$arrs=explode('-', $v['endtime']);
				if($arrs[1]==$times[1]){
					$data[$key]['whlx']=$v['nowinterest']-$v['returninterest'];
					$data[$key]['yhlx']=$v['returninterest'];
					break;
				}
			}
		}

		//统计数据
		foreach ($data as $k => $v) {
			$twhlx+=$v['whlx'];
			$tyhlx+=$v['yhlx'];
		}
		
		$count=count($data);
        $Page= $this->getPage($count,20);
        $this->show= $Page->show();
        $datas = array_slice($data,$Page->firstRow,$Page->listRows);

        $this->assign("count",$count);
		$this->assign("cashinfo",$datas);
		$this->assign("twhlx",$twhlx);
		$this->assign("tyhlx",$tyhlx);
		$this->display();
	}

	//速贷
	public function indexsd(){
		// var_dump($_SESSION);
		$kun=$this->conditions();
		$where[]=array("type"=>2);

		//添加搜索条件
		$post = I('get.');
		$stime = strtotime($post['stime']." 00:00:00");
		$endtime = strtotime($post['endtime']." 24:00:00");
		if(IS_GET){

			if($post['stime'] && $post['endtime'] && $post['sou']){

				$aid=M("member")->alias("m")->join("admin a on a.member_id=m.id")->Field("a.id")->where(array("m.username"=>array("like","%{$post['sou']}%")))->select();
				foreach ($aid as $value) {
					$aids[]=$value["id"];
				}
				$uid=M("cash")->where(array("name"=>array("like","%{$post['sou']}%")))->getField("id",true);
				if(count($aids)){
					$where['Salesman']=array("in",$aids);
				}else{
					$where["id"]=array("in",$uid);
				}
				$where[]=array("starttime"=>array(array("gt",$stime),array('elt',$endtime)));
				// $where[]=$kun;
			  }elseif($post['stime'] && $post['endtime'] && $post['branch']){

			  	if($post['branch']==33){
			  		$adimds=array(65,66,127,128,136);
			  	}else{
			  		$adimds=M("admin")->where("branch_id={$post['branch']}")->getField("id",true);
			  	}
			  	$where['Salesman']=array("in",$adimds);
			  	$where[]=array("starttime"=>array(array("gt",$stime),array('elt',$endtime)));

			  }elseif($post['stime'] && $post['endtime']){
			  	$where[]=array("starttime"=>array(array("gt",$stime),array('elt',$endtime)));
				// $where[]=$kun;
			  }elseif($post['branch']){
			  	if($post['branch']==33){
			  		$adimds=array(65,66,127,128,136);
			  	}else{
			  		$adimds=M("admin")->where("branch_id={$post['branch']}")->getField("id",true);
			  	}
			  	$where['Salesman']=array("in",$adimds);	

			  }elseif($post['sou']){
				  	$aid=M("member")->alias("m")->join("admin a on a.member_id=m.id")->Field("a.id")->where(array("m.username"=>array("like","%{$post['sou']}%")))->select();
					foreach ($aid as $value) {
						$aids[]=$value["id"];
					}
					$uid=M("cash")->where(array("name"=>array("like","%{$post['sou']}%")))->getField("id",true);
					if(count($aids)){
							$where['Salesman']=array("in",$aids);
						}else{
							$where["id"]=array("in",$uid);
						}
					// $where[]=$kun;
			  }

		}
		//添加搜索条件结束

		$where[] = $kun;
		$data=M("Cash")->where($where)->order("id desc")->select();
		foreach ($data as $key => $val) {
			$arr=M("member")->alias("m")->join("admin a on a.member_id=m.id")->where(array("a.id"=>$data[$key]['salesman']))->Field('m.username')->select();
			$data[$key]['salesman']=$arr['0']['username'];

			//滞纳金
			$latefee=M("Cash")->Field('money,returnlaf')->find($data[$key]['id']);
			$arr=M("Cashdetails")->where("uid={$data[$key]['id']} and repayment=1")->order('id asc')->limit(1)->Field('id,starttime')->select();
			$stime=strtotime($arr[0]['starttime']);
			$etime=time();
			if($stime>0){
				if($etime>$stime){
					$timediff = $etime-$stime;
					 $days = intval($timediff/86400);
				}else{
					 $days=0;
				}
			}else{
				 $days=0;
			}
			$znj=($days*$latefee['money']*0.005)-$latefee['returnlaf'];
			$datass['latefee']=0;
			M("Cash")->where("id={$data[$key]['id']}")->save($datass);
			$dataznj['latefee']=$znj;
			M("Cash")->where("id={$data[$key]['id']}")->save($dataznj);

			$data[$key]['latefee']=$znj;

			//应还利息跟未还利息
			$arr=M("Cashdetails")->where("uid={$data[$key]['id']}")->order('id asc')->Field('id,starttime,returninterest,nowinterest')->select();
			// var_dump($arrs[1]);
			$time=date("Y-m-d",time());
			$times=explode('-', $time);
			// var_dump($times[1]);
			foreach ($arr as $k => $v) {
				$arrs=explode('-', $v['starttime']);
				if($arrs[1]==$times[1]){
					$data[$key]['whlx']=$v['nowinterest']-$v['returninterest'];
					$data[$key]['yhlx']=$v['returninterest'];
					break;
				}
			}

		}

		//统计数据
		foreach ($data as $k => $v) {
			$twhlx+=$v['whlx'];
			$tyhlx+=$v['yhlx'];
		}

		// var_dump($data);
		$count=count($data);
        $Page= $this->getPage($count,20);
        $this->show= $Page->show();
        $datas = array_slice($data,$Page->firstRow,$Page->listRows);

        $this->assign("count",$count);
		$this->assign("cashinfo",$datas);
		$this->assign("twhlx",$twhlx);
		$this->assign("tyhlx",$tyhlx);
		$this->display();
	}

	public function yewu(){
		$id=I('get.id');
		$array=M('applys')->where("uid={$id}")->find();
		// var_dump($array);
		$this->assign('data',$array);
		$this->assign('id',$id);
		$this->display();
	}

	public function edit(){
		$id=$_GET['id'];
		$data=M("Cashdetails")->where("uid={$id}")->select();
		//还款详情
		$infos=M("Cashinfo")->where("uid={$id}")->select();
		$i=0;
		foreach ($infos as $k => $val){
				$info[$k]=$val;
				$info[$k]['time']=date("Y-m-d H:i:s",$val['time']);
				$i++;
				$info[$k]['bs']=$i;
		}
		
		//滞纳金
		$latefee=M("Cash")->Field('money,returnlaf')->find($id);
		$arr=M("Cashdetails")->where("uid={$id} and repayment=1")->order('id asc')->limit(1)->Field('id,endtime')->select();
		foreach ($arr as $k => $val) {
			$arrs[]=$val['endtime'];
		}
		// var_dump($arrs[0]);
		$stime=strtotime($arrs[0]);
		$etime=time();
		if($stime>0){
			if($etime>$stime){
				$timediff = $etime-$stime;
				 $days = intval($timediff/86400);
			}else{
				 $days=0;
			}
		}else{
			 $days=0;
		}
		// echo $days;
		$znj=($days*$latefee['money']*0.003)-$latefee['returnlaf'];
		$datass['Latefee']=0;
		M("Cash")->where("id={$id}")->save($datass);
		$dataznj['Latefee']=$znj;
		M("Cash")->where("id={$id}")->save($dataznj);
		
		$this->assign("znj",$znj);
		$this->assign("ids",$id);
		$this->assign("details",$data);
		$this->assign("info",$info);
		$this->display();
	}

	public function editsd(){
		$id=$_GET['id'];
		$data=M("Cashdetails")->where("uid={$id}")->select();
		//还款详情
		$infos=M("Cashinfo")->where("uid={$id}")->select();
		$i=0;
		foreach ($infos as $k => $val){
				$info[$k]=$val;
				$info[$k]['time']=date("Y-m-d H:i:s",$val['time']);
				$i++;
				$info[$k]['bs']=$i;
		}
		// var_dump($info);
		//滞纳金
		$latefee=M("Cash")->Field('money,returnlaf')->find($id);
		$arr=M("Cashdetails")->where("uid={$id} and repayment=1")->order('id asc')->limit(1)->Field('id,starttime')->select();
		foreach ($arr as $k => $val) {
			$arrs[]=$val['starttime'];
		}
		// var_dump($arrs[0]);
		$stime=strtotime($arrs[0]);
		$etime=time();
		if($stime>0){
			if($etime>$stime){
				$timediff = $etime-$stime;
				 $days = intval($timediff/86400);
			}else{
				 $days=0;
			}
		}else{
			 $days=0;
		}
		// echo $days;
		$znj=($days*$latefee['money']*0.005)-$latefee['returnlaf'];

		$datass['Latefee']=0;
		M("Cash")->where("id={$id}")->save($datass);
		$dataznj['Latefee']=$znj;
		M("Cash")->where("id={$id}")->save($dataznj);

		$this->assign("znj",$znj);
		$this->assign("ids",$id);
		$this->assign("details",$data);
		$this->assign("info",$info);
		$this->display();
	}

	public function apply(){
		$this->display();
	}

	public function listCustomer(){
		$arr=M("state")->where("type=4")->Field("id")->select();
		foreach ($arr as $k => $v) {
			$arrs[]=$v['id'];
		}
		$data=$this->model->alias("u")->join("state s on u.state_id=s.id")->where(array("u.state_id"=>array("in",$arrs)))->Field('s.id as sid,s.name as sname,s.addtime as saddtime,s.*,u.*')->select();
		foreach ($data as $k => $val) {
			$data[$k]=$val;
			$data[$k]['addtime']=date("Y-m-d H:i:s",$val['addtime']);
			if($val['lxtime']){
			$data[$k]['lxtime']=date("Y-m-d H:i:s",$val['lxtime']);
			}
			$array=M('member')->Field('username')->find($data[$k]['member_id']);
			$data[$k]['admin_id']=$array['username'];
		}
		$this->assign("data",$data);
		$this->assign("count",count($data));
		$this->display();
	}

	public function listqysystem(){
		$arr=M("qysystem")->select();
		foreach ($arr as $k => $v) {
			$data[$k]=$v;
			$data[$k]['time']=date('Y-m-d',$v['time']);
			$admins=M('admin')->field('member_id')->find($v['salesman']);
			$admin=M('member')->field('username')->find($admins['member_id']);
			$data[$k]['username']=$admin['username'];
		}
		$this->assign("data",$data);
		$this->assign("count",count($data));
		$this->display();
	}

	public function delsystem(){
		$id=$_GET['id'];

		$num=M("qysystem")->delete($id);
		if($num){
				$this->success('删除成功',U('Monetary/listqysystem'));
			}else{
				$this->error('删除失败',U('Monetary/listqysystem'));
			}
	}


	public function addindex(){
		
		$_GET["starttime"]=strtotime($_GET["starttime"]." 00:00:00");
		$_GET["endtime"]=strtotime($_GET["endtime"]." 20:00:00");
		$id=M("Cash")->add($_GET);
		if($id){
			for($i=1;$i<=$_GET["num"];$i++){
				$data["uid"]=$id;
				$data["num"]=$i;
				$data["Principal"]=$_GET["money"];
				$data["rate"]=$_GET["rate"];
				if($i==1){
					$stime=date('Y-m-d',$_GET["starttime"]);
					$data["starttime"]= $stime;
				}else{
					$data["starttime"]=$cftime;
				}
			
		$arr=explode("-", $data["starttime"]);
		if($arr['1']==4 || $arr['1']==6 ||$arr['1']==9 || $arr['1']==11){
				if(($arr['2']-1)==0){
					//防止月份前多出现0
					$arr['1']=$arr['1']+0;
					$arr['2']=30;
				}else{
					$arr['1']=$arr['1']+1;
					$arr['2']=$arr['2']-1;
				}
			
		}else if($arr['1']==2){
				if(($arr['2']-1)==0  &&  $arr['1']==2 && $arr['0']%4==0){
					//防止月份前多出现0
					$arr['1']=$arr['1']+0;
					$arr['2']=29;
				}else if(($arr['2']-1)==0  &&  $arr['1']==2){
					$arr['1']=$arr['1']+0;
					$arr['2']=28;
				}else{
					$arr['1']=$arr['1']+1;
					$arr['2']=$arr['2']-1;
				}
				
		}else{
			//1,3,5,7,8,10,12
			if($arr['1']==12){
				if(($arr['2']-1)==0){
					// $arr['1']--;
					$arr['1']=$arr['1']+0;
					$arr['2']=31;
				}else{
					$arr['0']=$arr['0']+1;
					$arr['1']=1;
					$arr['2']=$arr['2']-1;
				}
			}else{

				if(($arr['2']-1)==0){
					// $arr['1']--;
					$arr['1']=$arr['1']+0;
					$arr['2']=31;
				}else{
					$arr['1']=$arr['1']+1;
					$arr['2']=$arr['2']-1;
				}

			}
			
		}
		$arr['1']=$arr['1']<10?'0'.$arr['1']:$arr['1'];
		$arr['2']=$arr['2']<10?'0'.$arr['2']:$arr['2'];
		$endtime=implode($arr, "-");

				
				//速贷速分贷月本金根止息时间
				if($_GET["num"]>4){
					$data["nowpri"]=$_GET["money"]/$_GET["num"];
					$data["endtime"]=$endtime;
				}elseif ($_GET["num"]<4 && ($i==$_GET["num"])) {
					$data["endtime"]=$endtime;
					$data["nowpri"]=$_GET["money"];
				}else{
					$data["endtime"]='';
					$data["nowpri"]=0;
				}

				$data["nowinterest"]=$_GET["accumulative"]/$_GET["num"];
				$data["returnpri"]=0;
				$data["returninterest"]=0;
				$data["Arrears"]=$data["nowpri"]+$data["nowinterest"];
				$data["repayment"]=1;
				
				$Cashdetailsid=M("Cashdetails")->add($data);
				
				$arrs=explode('-', $endtime);
				$arrs['1']=$arrs['1']<10?$arrs['1']['1']:$arrs['1'];
				$arrs['2']=$arrs['2']<10?$arrs['2']['1']:$arrs['2'];
				
				if($arrs['1']==4 || $arrs['1']==6 ||$arrs['1']==9 || $arrs['1']==11){
					if($arrs['2']==30){
						$arrs['1']++;
						$arrs['2']=1;
					}else{
						$arrs['2']++;
					}
				
				}else if($arrs['1']==1 || $arrs['1']==3 || $arrs['1']==5 || $arrs['1']==7 || $arrs['1']==8 || $arrs['1']==10){
					if($arrs['2']==31){
						$arrs['1']++;
						$arrs['2']=1;
					}else{
						$arrs['2']++;
					}

				}else if($arrs['2']==29 && $arrs['0']%4==0 && $arrs['1']==2){
					$arrs['1']=3;
					$arrs['2']=1;
				}else if($arrs['2']==28 && $arrs['1']==2){
					$arrs['1']=3;
					$arrs['2']=1;
				}else if($arrs['1']==12 && $arrs['2']==31){
					$arrs['0']++;
					$arrs['1']=1;
					$arrs['2']=1;
				}else{
					$arrs['2']++;
				}
				$arrs['1']=$arrs['1']<10?'0'.$arrs['1']:$arrs['1'];
				$arrs['2']=$arrs['2']<10?'0'.$arrs['2']:$arrs['2'];
				$cftime=implode($arrs, "-");
			}

			if($Cashdetailsid<0){
				M("Cash")->delete($id);
			}
			if($Cashdetailsid>0){
				if($_GET['leixing']=='sd'){
					$this->redirect("Monetary/indexsd");
				}else{
					$this->redirect("Monetary/index");
				}
			}
						
		}

			$this->display();
	}


	public function qrinfo(){
		
		foreach ($_POST as $key => $value) {
			if($key != 'id'){
				$data[$key]=$value;
			}
		}
		if(!empty($_FILES)){
			$upload=new \Think\Upload();
			$upload->maxSize   =     5200000;
			$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg', 'rar', 'zip');
	    	$upload->rootPath  =     './Uploads/'; 
	   		$upload->savePath  =     "Files_xx/{$_POST['id']}/";
	    	// $info = $upload->upload();

		    foreach($_FILES as $key=>$value){
	          if(count($_FILES[$key]) == count($_FILES[$key],1)){
	            $info = $upload->uploadOne($_FILES[$key]);
	          }
	        }

	         if(count($_FILES)){
	          $info = $upload->upload();//如果是二维数组，使用批量上传文件的方法
	          // var_dump($info);
	          $img_url = './Uploads/'.$info[0]['savepath'].$info[0]['savename'];
	          $img_url1 = './Uploads/'.$info[1]['savepath'].$info[1]['savename'];
	          if(count($info) !=2){
	          	  unlink($img_url1);
	          	  unlink($img_url);
	              $this->error($upload->getError());
	              exit();
	            }
	          $data['housing']=$img_url;
	          $data['liabilities']=$img_url1;
	          $res = array('imgPath1'=>$img_url,code=>$img_url,'msg'=>$info);
	        }
		}
		// var_dump($data);exit();
		$num=M('applys')->where("uid={$_POST['id']}")->save($data);

		$arrs=array("lxtime"=>time(),"state_id"=>18);
		$this->model->where("id={$_POST['id']}")->save($arrs);

		if($num){
			$this->success('确认信息成功',U('Monetary/listCustomer'));
		}else{
			$this->error('确认信息失败',U('Monetary/listCustomer'));
		}

	}

	public function qrzl(){
		// var_dump($_POST);
		$str=implode(',',$_POST['certificates']);
		$data['certificates']=$str;

		foreach ($_POST as $key => $val) {
			if($key != 'id' && $key != 'certificates'){
				$data[$key]=$val;
			}
		}

		if(!empty($_FILES)){
			$upload=new \Think\Upload();
			$upload->maxSize   =     5200000;
			$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg', 'rar', 'zip');
	    	$upload->rootPath  =     './Uploads/'; 
	   		$upload->savePath  =     "Files_zl/{$_POST['id']}/";
	    	// $info = $upload->upload();

		    foreach($_FILES as $key=>$value){
	          if(count($_FILES[$key]) == count($_FILES[$key],1)){
	            $info = $upload->uploadOne($_FILES[$key]);
	          }
	        }

	         if(count($_FILES)){
	          $info = $upload->upload();//如果是二维数组，使用批量上传文件的方法
	          // var_dump($info);
	          $img_url = './Uploads/'.$info[0]['savepath'].$info[0]['savename'];
	          $img_url1 = './Uploads/'.$info[1]['savepath'].$info[1]['savename'];
	          if(count($info) !=2){
	          	  unlink($img_url1);
	          	  unlink($img_url);
	              $this->error($upload->getError());
	              exit();
	            }
	          $data['rehousing']=$img_url;
	          $data['reliabilities']=$img_url1;
	          $res = array('imgPath1'=>$img_url,code=>$img_url,'msg'=>$info);
	        }
		}

		$num=M('applys')->where("uid={$_POST['id']}")->save($data);
		$arrs=array("lxtime"=>time(),"state_id"=>19);
		$this->model->where("id={$_POST['id']}")->save($arrs);

		if($num){
			$this->success('确认资料成功',U('Monetary/listCustomer'));
		}else{
			$this->error('确认资料失败',U('Monetary/listCustomer'));
		}

		
	}

	public function qrfy(){
		foreach ($_POST as $key => $val) {
			if($key != 'id'){
				$data[$key]=$val;
			}
		}
		$num=M('applys')->where("uid={$_POST['id']}")->save($data);
		$arr=array("lxtime"=>time(),"state_id"=>20);
		$this->model->where("id={$_POST['id']}")->save($arr);
		if($num){
			$this->success('确认费用成功',U('Monetary/listCustomer'));
		}else{
			$this->error('确认费用失败',U('Monetary/listCustomer'));
		}

	}

	//获取业务员
	public function memberss(){
		$rid= I("post.rid");

		$arr=M("admin")->alias("a")->field("m.username,a.id")->join("member m ON a.member_id=m.id")->where("a.level_id ={$rid}")->select();
		
		echo  $this->ajaxReturn($arr);

	}


	public function upd(){

		$arr=M("cashdetails")->field("id,nowpri,nowinterest,returnpri,returninterest,arrears,principal")->where("uid={$_POST['id']} and repayment=1")->select();

		if($arr){
			$znjs=M("cash")->field("Latefee,returnlaf")->find($_POST['id']);
			$znj=$znjs['latefee'];
			$ylx=$arr[0]['nowinterest'];
			$ybj=$arr[0]['nowpri'];
			$hkje=$_POST["hkje"];
			foreach ($arr as $key => $val) {
				$arrs[]=$val['id'];
				$total+=$val['arrears'];
			}

			$data["returnpri"]=$ybj;
			$data["returninterest"]=$ylx;
			$data['Arrears']=0;
			$data['repayment']=2;
			$data['Principal']=0;
			$infos=array();
			$infos["uid"]=$_POST['id'];
			$infos["time"]=time();
			

			if($hkje==($total+$znj)){
				foreach ($arrs as $val) {
					$num=M("cashdetails")->where("id={$val}")->save($data);
				}
				if($num){
					$cah['Latefee']=0;
					$cah['returnlaf']=0;
					M("Cash")->where("id={$_POST['id']}")->save($cah);
					//插入明细表
					$infos["sum"]=$hkje;
					$infos["returnlf"]=$znj;
					M("cashinfo")->add($infos);
					echo  "还款成功";
				}

			}elseif($hkje<=$znj){
				
				$cah["returnlaf"]=$hkje+$znjs['returnlaf'];
				$num=M("Cash")->where("id={$_POST['id']}")->save($cah);
				if($num){
					//插入明细表
					$infos["sum"]=$hkje;
					$infos["returnlf"]=$hkje;
					M("cashinfo")->add($infos);
					echo "还款成功";
				}

			}elseif($hkje>$znj){

				//插入明细表
				$infos["sum"]=$hkje;
				$infos["returnlf"]=$znj;
				M("cashinfo")->add($infos);

				//循环还款期数
				for($i=0; $i<count($arr); $i++){
					if($i==0){
						if($hkje < ($arr[$i]['arrears']+$znj)){

							if($znj > 0){
								$cah["returnlaf"]=$znj+$znjs['returnlaf'];
							}
							$num=M("Cash")->where("id={$_POST['id']}")->save($cah);
							//判断repayment=1的第一个是否有还利息
							  if($arr[$i]['returninterest']>0){
								if((($hkje+$arr[$i]['returninterest'])-($znj+$ylx))>0){
									$detail['returninterest']=$ylx;
									//判断repayment=1的第一个是否有还本金；
									if($arr[$i]['returnpri']>0){
									  $detail['returnpri']=(($hkje+$arr[$i]['returninterest'])-($znj+$ylx))+$arr[$i]['returnpri'];
									  $bjd=$hkje-$znj;
									}else{
									  $detail['returnpri']=($hkje)-(($znj+$ylx)-$arr[$i]['returninterest']);
									  $bjd=$detail['returnpri'];
									}
									
									$detail['Arrears']=($ylx+$ybj)-($detail['returninterest']+$detail['returnpri']);
									M("cashdetails")->where("id={$arr[$i]['id']}")->save($detail);

									//计算本金余额
									$bj['Principal']=$arr[$i]['principal']-$bjd;
									$bjs=$bj['Principal'];
									foreach ($arrs as $val) {
											M("cashdetails")->where("id={$val}")->save($bj);
										}

								}else{
									$detail['returninterest']=$hkje-$znj+$arr[$i]['returninterest'];
									$detail['returnpri']=0;
									$detail['Arrears']=($ylx+$ybj)-$detail['returninterest'];
									M("cashdetails")->where("id={$arr[$i]['id']}")->save($detail);

								}

							  }else{

							  	if(($hkje-$znj-$ylx)>0){
									$detail['returninterest']=$ylx;
									$detail['returnpri']=$hkje-$znj-$ylx;
									$detail['Arrears']=($ylx+$ybj)-($detail['returninterest']+$detail['returnpri']);
									M("cashdetails")->where("id={$arr[$i]['id']}")->save($detail);

									//计算本金余额
									$bj['Principal']=$arr[$i]['principal']-$detail['returnpri'];
									$bjs=$bj['Principal'];
									foreach ($arrs as $val) {
											M("cashdetails")->where("id={$val}")->save($bj);
										}

								}else{
									$detail['returninterest']=$hkje-$znj;
									$detail['returnpri']=0;
									$detail['Arrears']=($ylx+$ybj)-$detail['returninterest'];
									M("cashdetails")->where("id={$arr[$i]['id']}")->save($detail);
									
								}
							  }
							  
							break;
						}else{
							//整条插入改变repayment的值
							$cah["returnlaf"]=0;
							$num=M("Cash")->where("id={$_POST['id']}")->save($cah);

							$detail['returninterest']=$ylx;
							$detail['returnpri']=$ybj;
							$detail['repayment']=2;
							$detail['Arrears']=0;
							M("cashdetails")->where("id={$arr[$i]['id']}")->save($detail);

							if($arr[$i]['returnpri']>0){
									  $bjd=$ybj-$arr[$i]['returnpri'];
									}else{
									  $bjd=$detail['returnpri'];
									}
							//计算本金余额
							$bj['Principal']=$arr[$i]['principal']-$bjd;
							$bjs=$bj['Principal'];
							// echo $bj['Principal'].'aaa';
							foreach ($arrs as $val) {
									M("cashdetails")->where("id={$val}")->save($bj);
								}
								
							$hkje=$hkje-($znj+$arr[$i]['arrears']);
							
						}
					}else{
						
						if($hkje < $arr[$i]['arrears']){
							
							if($hkje){
								if(($hkje-$ylx)>0){
									$detail['returninterest']=$ylx;
									$detail['returnpri']=$hkje-$ylx;
									$detail['repayment']=1;
									$detail['Arrears']=($ylx+$ybj)-($detail['returninterest']+$detail['returnpri']);
									M("cashdetails")->where("id={$arr[$i]['id']}")->save($detail);

									// 计算本金余额
									// echo $arr[$i]['returnpri'];
									if($arr[$i]['returnpri']>0){
									  $bjd=$ybj-$arr[$i]['returnpri'];
									}else{
									  $bjd=$detail['returnpri'];
									}
									
									$bj['Principal']=$bjs-$bjd;
									$bjs=$bj['Principal'];
									$array=array_slice($arrs,$i);
									foreach ($array as $val) {
											M("cashdetails")->where("id={$val}")->save($bj);
										}

								}else{
									$details['returninterest']=$hkje;
									$details['returnpri']=0;
									$details['repayment']=1;
									$details['Arrears']=($ylx+$ybj)-$hkje;
									M("cashdetails")->where("id={$arr[$i]['id']}")->save($details);
									
								}
							}
							break;
						}else{
							
							$detaila['returninterest']=$ylx;
							$detaila['returnpri']=$ybj;
							$detaila['repayment']=2;
							$detaila['Arrears']=0;
							M("cashdetails")->where("id={$arr[$i]['id']}")->save($detaila);

							//计算本金余额
							$bj['Principal']=$bjs-$ybj;
							$bjs=$bj['Principal'];
							$array=array_slice($arrs,$i);
							foreach ($array as $val) {
									M("cashdetails")->where("id={$val}")->save($bj);
								}
							$hkje=$hkje-$arr[$i]['arrears'];
						}
					}
				}
				echo "还款成功";
			}

		}else{
			echo "没有需要还的贷款";
		}
	}


	public function updsd(){

		$arr=M("cashdetails")->field("id,nowinterest,returnpri,returninterest,arrears,principal")->where("uid={$_POST['id']} and repayment=1")->select();

		if($arr){

			$znjs=M("cash")->field("Latefee,returnlaf")->find($_POST['id']);
			$znj=$znjs['latefee'];
			$ylx=$arr[0]['nowinterest'];
			$bj=$arr[0]['principal'];
			$hkje=$_POST["hkje"];
			foreach ($arr as $key => $val) {
				$arrs[]=$val['id'];
				$total+=$val['arrears'];
			}
			
			$data["returnpri"]=0;
			$data["returninterest"]=$ylx;
			$data['Arrears']=0;
			$data['repayment']=2;
			$data['Principal']=$bj;
			$infos=array();
			$infos["uid"]=$_POST['id'];
			$infos["time"]=time();
			
			// echo $hkje.'ss';
			// echo $total+$znj.'ss'.$hkje; exit();
			if($hkje==($total+$znj)){
				
				$i=0;
				foreach ($arrs as $val) {
					$i++;
					if($i==count($arrs)){
						$data["returnpri"]=$bj;
						$data['Principal']=0;
					}
					$num=M("cashdetails")->where("id={$val}")->save($data);
				}
				if($num){
					$cah['Latefee']=0;
					$cah['returnlaf']=0;
					M("Cash")->where("id={$_POST['id']}")->save($cah);
					//插入明细表
					$infos["sum"]=$hkje;
					$infos["returnlf"]=$znj;
					M("cashinfo")->add($infos);
					echo  "还款成功";
				}

			}elseif($hkje<=$znj){
				
				$cah["returnlaf"]=$hkje+$znjs['returnlaf'];
				$num=M("Cash")->where("id={$_POST['id']}")->save($cah);
				if($num){
					//插入明细表
					$infos["sum"]=$hkje;
					$infos["returnlf"]=$hkje;
					M("cashinfo")->add($infos);
					echo "还款成功";
				}

			}elseif($hkje>$znj){
				// echo $hkje; exit();
				//插入明细表
				$infos["sum"]=$hkje;
				$infos["returnlf"]=$znj;
				M("cashinfo")->add($infos);
				
				//循环还款期数
				for($i=0; $i<count($arr); $i++){

					if($i==0){
						// echo 'sss';
						// echo $arr[$i]['arrears']+$znj;exit();
						if($hkje < ($arr[$i]['arrears']+$znj)){

							if($znj > 0){
								$cah["returnlaf"]=$znj+$znjs['returnlaf'];
							}
							$num=M("Cash")->where("id={$_POST['id']}")->save($cah);
							//判断repayment=1的第一个是否有还利息
							  if($arr[$i]['returninterest']>0){

								if((($hkje+$arr[$i]['returninterest'])-($znj+$ylx))>0){

									$detail['returninterest']=$ylx;
									 //判断是否最后一次
									if(count($arr)==1){
										//判断repayment=1的第一个是否有还本金；
										if($arr[$i]['returnpri']>0){
										  $detail['returnpri']=(($hkje+$arr[$i]['returninterest'])-($znj+$ylx))+$arr[$i]['returnpri'];
										  // $bjd=$hkje-$znj;
										}else{
										  $detail['returnpri']=$hkje-(($znj+$ylx)-$arr[$i]['returninterest']);
										  // $bjd=$detail['returnpri'];
										}
										$detail['Arrears']=($ylx+$bj)-($detail['returninterest']+$detail['returnpri']);
										// $detail['Principal']=$bj-$detail['returnpri'];
									}else{
										
										$detail['returnpri']=0;
										$detail['Arrears']=($ylx+$ybj)-($detail['returninterest']+$detail['returnpri']);
									}
									
									// $detail['Arrears']=($ylx+$ybj)-($detail['returninterest']+$detail['returnpri']);
									M("cashdetails")->where("id={$arr[$i]['id']}")->save($detail);

								}else{
									$detail['returninterest']=$hkje-$znj+$arr[$i]['returninterest'];
									$detail['returnpri']=0;
									//是否最后一次
									if(count($arr)==1){
										$detail['Arrears']=($ylx+$bj)-$detail['returninterest'];
									}else{
										$detail['Arrears']=$ylx-$detail['returninterest'];
									}
									
									M("cashdetails")->where("id={$arr[$i]['id']}")->save($detail);

								}

							  }else{

							  	if(($hkje-$znj-$ylx)>0){
									$detail['returninterest']=$ylx;
									//是否最后一次
									if(count($arr)==1){
										$detail['returnpri']=$hkje-$znj-$ylx;
										$detail['Arrears']=($ylx+$bj)-($detail['returninterest']+$detail['returnpri']);
										// $detail['Principal']=$bj-$detail['returnpri'];
									}else{
										$detail['returnpri']=0;
										$detail['Arrears']=$ylx-($detail['returninterest']+$detail['returnpri']);
									}
									
									M("cashdetails")->where("id={$arr[$i]['id']}")->save($detail);

								}else{
									$detail['returninterest']=$hkje-$znj;
									$detail['returnpri']=0;
									if(count($arr)==1){
										$detail['Arrears']=($ylx+$bj)-$detail['returninterest'];
									}else{
										$detail['Arrears']=($ylx+$ybj)-$detail['returninterest'];
									}
									
									M("cashdetails")->where("id={$arr[$i]['id']}")->save($detail);
									
								}
							  }
							  
							break;
						}else{
							//整条插入改变repayment的值
							// echo $hkje; exit();
							$cah["returnlaf"]=0;
							$num=M("Cash")->where("id={$_POST['id']}")->save($cah);

							$detail['returninterest']=$ylx;
							//判断最后一次
							if(count($arr)==1){
								$detail['returnpri']=$bj;
								// $detail['Principal']=$bj-$detail['returnpri'];
							}else{
								$detail['returnpri']=0;
							}
							// echo $detail['returnpri'];exit();

							$detail['repayment']=2;
							$detail['Arrears']=0;
							M("cashdetails")->where("id={$arr[$i]['id']}")->save($detail);

							$hkje=$hkje-($znj+$arr[$i]['arrears']);
							// echo $hkje; exit();
						}
					}else{
						
						if($hkje < $arr[$i]['arrears']){
							
							if($hkje){
								if(($hkje-$ylx)>0){
									$detail['returninterest']=$ylx;
									//判断最后一次
									if($i==(count($arr)-1)){
											$detail['returnpri']=$hkje-$ylx;
											$detail['Arrears']=($ylx+$bj)-($detail['returninterest']+$detail['returnpri']);
											// $detail['Principal']=$bj-$detail['returnpri'];
										}else{
											$detail['returnpri']=0;
											$detail['Arrears']=($ylx+$ybj)-($detail['returninterest']+$detail['returnpri']);
										}

									$detail['repayment']=1;
									
									M("cashdetails")->where("id={$arr[$i]['id']}")->save($detail);

								}else{
									$details['returninterest']=$hkje;
									$details['returnpri']=0;
									$details['repayment']=1;
									//判断最后一次
									if($i==(count($arr)-1)){
											$details['Arrears']=($ylx+$bj)-$hkje;
										}else{
											$details['Arrears']=$ylx-$hkje;
										}
									// $details['Arrears']=($ylx+$ybj)-$hkje;
									M("cashdetails")->where("id={$arr[$i]['id']}")->save($details);
									
								}
							}
							break;
						}else{
							
							$detaila['returninterest']=$ylx;

							//判断最后一次
							if($i==(count($arr)-1)){
									$detaila['returnpri']=$bj;
									// $detaila['Principal']=$bj-$detaila['returnpri'];
								}else{
									$detaila['returnpri']=0;
								}
							
							$detaila['repayment']=2;
							$detaila['Arrears']=0;
							M("cashdetails")->where("id={$arr[$i]['id']}")->save($detaila);

							$hkje=$hkje-$arr[$i]['arrears'];
						}
					}
				}
				echo "还款成功";
			}

		}else{
			echo "没有需要还的贷款";
		}
	}

	public function dels(){

		$id=I('get.id');
		$arr=M('cash')->where("id={$id}")->getField("type", true);
		$type=$arr['0'];
		$num=M("cash")->where("id={$id}")->delete();
		if($num){
			M("cashinfo")->where("uid={$id}")->delete();
			$nums=M("cashdetails")->where("uid={$id}")->delete();
			if($nums){
					if($type==1){
						$this->success('删除成功',U('Monetary/index'));
					}else{
						$this->success('删除成功',U('Monetary/indexsd'));
					}
				}else{
					if($type==1){
						$this->success('删除失败',U('Monetary/index'));
					}else{
						$this->success('删除失败',U('Monetary/indexsd'));
					}
			}
		}
	}

	public function del(){
		
		$id=$_GET['id'];
		$num=M("applys")->where("uid={$id}")->delete();
		if($num){
			$numss=M("Uclient")->where("id={$id}")->delete();
			if($numss){
					$this->success('删除成功',U('Monetary/listCustomer'));
				}else{
					$this->error('删除失败',U('Monetary/listCustomer'));
			}
		}

	}

	public function infos(){

		$id=$_GET['id'];
		$data=M("applys")->alias("a")->join("Uclient u ON a.uid=u.id")->where("a.uid={$id}")->find();
		$arr=M("member")->field("username")->find($data['member_id']);
		$data['admin_name']=$arr['username'];
		$this->assign('data',$data);
		$this->display();
	}

	public function infosystem(){

		$id=$_GET['id'];
		$data=M("qysystem")->find($id);
		// var_dump($data);
		$admins=M('admin')->field('member_id')->find($data['salesman']);
		$admin=M('member')->field('username')->find($admins['member_id']);
		$data['username']=$admin['username'];
		$this->assign('data',$data);
		$this->display();
	}

}

 ?>