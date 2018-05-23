<?php 
namespace Admin\Controller;
use Think\Controller;
use \Org\Util\Data;

class ApplyController extends Controller{


	protected $model;
	
	public function index(){

		$this->display();
	}

	//获取业务员
	public function memberss(){
		$rid= I("post.rid");

		$arr=M("admin")->alias("a")->field("m.username,a.id")->join("member m ON a.member_id=m.id")->where("a.level_id ={$rid}")->select();
		
		echo  $this->ajaxReturn($arr);

	}

	public function add(){
			if(!empty($_FILES)){
				$upload=new \Think\Upload();
				$upload->maxSize   =     10000000;
				$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');
		    	$upload->rootPath  =     './Uploads/'; 
		   		$upload->savePath  =     "Files_photo/";
			}
			$info = $upload->upload();
			if(!$info) {  
				    $this->error($upload->getError());  
				}else{
				    foreach($info as $file){  
				        $adata['photo'] = './Uploads/'.$file['savepath'].$file['savename'];
				        $_POST['photo'] = './Uploads/'.$file['savepath'].$file['savename'];
				    }

				$_POST['state_id']=17;
				$_POST['money']=$_POST['money']*10000;
				$_POST['addtime']=time();
				$ids=M("applys")->add($_POST);
				if($ids>0){
					$files['aid']=$ids;
					M("cashfiles")->add($files);
					echo "<script>alert('添加资料成功')</script>";
					$this->success('页面即将跳转...','http://47.92.119.237:8888/Signing/');
				}else{
					echo "<script>alert('添加资料失败')</script>";
					$this->error('页面即将跳转...', U('Apply/index'));
				}
			} 
	}


	public function selectphone(){
		$phone=I('get.phone');
		
		$num=M('Uclient')->where("phone={$phone}")->select();

		if($num){
			echo 1;
		}
	}

	public function upd(){
		$data=I('post.');
		$id=I('post.id');
		$data['money']=$data['money']*10000;
		
		if($_FILES['photo']['name']){
			$upload=new \Think\Upload();
			$upload->maxSize   =     10000000;
			$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');
	    	$upload->rootPath  =     './Uploads/'; 
	   		$upload->savePath  =     "Files_photo/{$id}/";
			$info = $upload->upload();
			if(!$info) {  
				    $this->error($upload->getError());  
				}else{
				    foreach($info as $file){  
				        $data['photo'] = './Uploads/'.$file['savepath'].$file['savename']; 
				    } 
				    unlink($data['oldphoto']);
				}
		}else{
			unset($data['photo']);
		}

		$nums=M("Applys")->where("id={$id}")->save($data);
		if($num>0 || $nums>0){
			$this->success('修改成功...', U('/Admin/Monetary/listCustomer'));
		}else{
			$this->error('修改失败...', U('/Admin/Monetary/listCustomer'));
		}
	}

	public function yewuall(){
		$this->display();
	}


	public function yewuone(){
		
		if($_FILES){
			$this->assign('ziduan',$_POST['zd']);
			if($_FILES["{$_POST['zd']}"]['name']){
				$id=$_POST['id'];
				$upload=new \Think\Upload();
				$upload->maxSize   =     10000000;
				$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');
		    	$upload->rootPath  =     './Uploads/'; 
		   		$upload->savePath  =     "Files_xjzlqr/{$id}/";
				$info = $upload->upload();
				if(!$info) {  
					    $this->error($upload->getError());  
					}else{
					    foreach($info as $file){  
					        $datas["{$_POST['zd']}"] = './Uploads/'.$file['savepath'].$file['savename']; 
					    } 
					}
				$row=M("cashfiles")->where("aid={$id}")->save($datas);
				if($row){
					echo "<script>alert('资料保存成功');window.location.href='/Admin/Apply/yewuone?id={$id}'</script>";
				}else{
					// $this->error("资料保存成功","/Admin/Apply/yewuone?id={$id}");
					echo "<script>alert('资料保存失败');window.location.href='/Admin/Apply/yewuone?id={$id}'</script>";
				}
			}else{
				$this->error("请上传资料");
			}
		}
		$data=M("cashfiles")->where("aid={$_GET['id']}")->Field("qg_law,sz_law,qcc,qxb,house_monitor,sl,lj,lyj")->find();
		foreach ($data as $k => $val) {
			if($val){
				$str .=$k.',';
			}
		}
		// var_dump(rtrim($str,','));
		// var_dump($data);
		$this->assign('str',rtrim($str,','));
		$this->display();
	}
	//预览
	public function preview(){
		$id=$_GET['id'];
		$zd=$_GET['zd'];
		$arr=M("cashfiles")->where("aid={$id}")->Field("{$zd}")->find();
		if($arr["{$zd}"]){
			$imgurl='http://47.92.119.237'.ltrim($arr["{$zd}"],'.');
			echo "<center><img align='left' src=".$imgurl."></center>";
		}else{
			echo "<script>alert('没有找到你要的资料');window.close();</script>";
		}		
	}

	public function downloadpic(){
		$id=$_GET['id'];
		$row=M('cashfiles')->where("aid={$id}")->Field('qg_law,sz_law,qcc,qxb,house_monitor,sl,lj,lyj')->find();
		
		// $pic ='http://www.crm.com/'.ltrim($row['photo'],'./');
		// $pic="http://www.jiakaodashi.com/ocr/images/hero-intro-pic-ocr.png";
		// $pic1="http://www.jiakaodashi.com/ocr/images/hero-intro-pic-ocr.png";
		// $arr=array($pic,$pic1);
		foreach ($row as $val) {
			if($val){
				$vals='http://47.92.119.237'.$val;
				$img.="<img src=".$vals.">";
			}
		}
				$filename=iconv('utf-8','gb2312',$id);
				header('pragma:public');  
				header('Content-type:application/vnd.ms-word;charset=utf-8;name="'.$filename.'".doc');  
				header("Content-Disposition:attachment;filename=$filename.doc");
				$html = '<html xmlns:o="urn:schemas-microsoft-com:office:office"  
				xmlns:w="urn:schemas-microsoft-com:office:word"  
				xmlns="http://www.w3.org/TR/REC-html40"><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>"'.$img.'"';
				echo $html.'</html>';
	} 

	public function over(){
		$_GET['lxtime']=time();
		$num=M('applys')->save($_GET);
		if($num){
			$this->success("已完成。。。", U('/Admin/Monetary/listCustomer'));
		}else{
			$this->success("未完成。。。", U('/Admin/Monetary/listCustomer'));
		}
	}

	public function yewutwo(){
		$cid=$_GET['id'];
		$data=M("cashfiles")->where("aid={$cid}")->find();
		// var_dump($data);
		$this->assign('data',$data);
		$this->display();
	}

	public function qr_info(){
		
		$str=implode(',',$_POST['certificates']);

		$data['certificates']=$str;
		// var_dump($data['certificates']);exit();
		$cid=$_POST['id'];
		foreach ($_POST as $key => $val) {
			if($key != 'id' && $key != 'certificates'){
				$data[$key]=$val;
			}
		}
		if(!empty($_FILES)){
			$upload=new \Think\Upload();
			$upload->maxSize   =     10000000;
			$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg', 'rar', 'zip');
	    	$upload->rootPath  =     './Uploads/'; 
	   		$upload->savePath  =     "Files_xjzlqr/{$_POST['id']}/";
	    	
		    foreach($_FILES as $key=>$value){
	          if(count($_FILES[$key]) == count($_FILES[$key],1)){
	          	if($key=="credit"){
	          		$info = $upload->uploadOne($_FILES[$key]);
	          		if($info){
	          			$data['credit']='./Uploads/'.$info['savepath'].$info['savename'];
	          		}
	          	}
	          	if($key=="flow"){
	          		$info = $upload->uploadOne($_FILES[$key]);
	          		if($info){
	          			$data['flow']='./Uploads/'.$info['savepath'].$info['savename'];
	          		}
	          	}
	          	if($key=="housing"){
	          		$info = $upload->uploadOne($_FILES[$key]);
	          		if($info){
	          			$data['housing']='./Uploads/'.$info['savepath'].$info['savename'];
	          		}
	          	}
	          	if($key=="liabilities"){
	          		$info = $upload->uploadOne($_FILES[$key]);
	          		if($info){
	          			$data['liabilities']='./Uploads/'.$info['savepath'].$info['savename'];
	          		}
	          	}
	            
	          }
	        }
		}
		
		$row=M("cashfiles")->where("aid={$cid}")->save($data);
		if($row){
			$this->error("资料保存成功","/Admin/Apply/yewutwo?id={$cid}");
		}else{
			$this->error("资料保存失败","/Admin/Apply/yewutwo?id={$cid}");
		}
	}

	public function yewuthree(){
		$cid=$_GET['id'];
		$data=M("cashfiles")->where("aid={$cid}")->find();
		// var_dump($data);
		$this->assign('data',$data);
		$this->display();
	}

	public function qrfy(){
		$row=M("cashfiles")->where("aid={$_POST['cid']}")->save($_POST);
		if($row){
			$this->error("资料保存成功","/Admin/Apply/yewuthree?id={$_POST['cid']}");
		}else{
			$this->error("资料保存失败","/Admin/Apply/yewuthree?id={$_POST['cid']}");
		}
	}

	public function yewufour(){
		$id=$_GET['id'];
		$data=M("applys")->where("id={$id}")->find();
		// var_dump($data);
		$this->assign('data',$data);
		$this->display();
	}

	public function administrate(){
		$id=$_GET['id'];
		$data=M("cash")->where("aid={$id} and state=1")->select();
		$yjq=M("cash")->where("aid={$id} and state=2")->select();
		$arr=array();
		foreach ($data as $key => $value) {
			array_push($arr, $value['salesman']);
		}
		
		if($arr){
			$arrs=M("member")->alias("m")->join("admin a on a.member_id=m.id")->where(array("a.id"=>array('in',$arr)))->Field('m.username,a.id')->select();
			foreach ($data as $key => $value) {
				foreach ($arrs as $k => $v) {
					if($value['salesman']==$v['id']){
						$data[$key]['admins']=$v['username'];
					}
				}
			}
		}

		//index开始
		foreach ($data as $key => $val) {
			
			//滞纳金
			$latefee=M("Cash")->Field('money,returnlaf')->find($data[$key]['id']);
			$arr=M("Cashdetails")->where("uid={$data[$key]['id']} and repayment=1")->order('id asc')->Field('id,endtime')->select();	
			$sum=0;
			foreach ($arr as $k => $val) {
				$stime=strtotime($arr[$k]['endtime']);
				$etime=time();
				if(strtotime($arr[$k+1]['endtime'])>0){
					if(strtotime($arr[$k+1]['endtime'])>$etime){
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
					}else{
						$timediff = strtotime($arr[$k+1]['endtime'])-$stime;
						$days = intval($timediff/86400);
					}
				}else{
					if($etime>$stime){
						$timediff = $etime-$stime;
						$days = intval($timediff/86400);
					}else{
						 $days=0;
					}
				}
			
				$sum+=($days*$latefee['money']*0.003);

			}
			//滞纳金暂时关闭
			// $dataznj['Latefee']=$sum-$latefee['returnlaf'];
			$dataznj['Latefee']=0;

			M("Cash")->where("id={$data[$key]['id']}")->save($dataznj);

			//滞纳金暂时关闭
			// $data[$key]['latefee']=$sum-$latefee['returnlaf'];
			$data[$key]['latefee']=0;
			//滞纳金暂时关闭
			//逾期天数
			// $data[$key]['days']=$days;
			$data[$key]['days']=0;
			//应还利息跟未还利息
			$arr=M("Cashdetails")->where("uid={$data[$key]['id']}")->order('id asc')->limit(1)->Field('id,endtime,returninterest,nowinterest')->select();
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
			$tmoney+=$v['money'];
			$tfee+=$v['fee'];
			$tmargin+=$v['margin'];
			$taccumulative+=$v['accumulative'];
			$tlatefee+=$v['latefee'];
		}
		//index结束
		if(empty($data) && !empty($yjq)){
			$_GET['state_id']=25;
			$_GET['lxtime']=time();
			M('applys')->save($_GET);
		}
		
		$this->assign("twhlx",$twhlx);
		$this->assign("tyhlx",$tyhlx);
		$this->assign("tmoney",$tmoney);
		$this->assign("tfee",$tfee);
		$this->assign("tmargin",$tmargin);
		$this->assign("taccumulative",$taccumulative);
		$this->assign("tlatefee",$tlatefee);
		$this->assign('data',$data);
		$this->display();
	}

	public function settlement(){
		$id=$_GET['id'];
		$dhgl=M("cash")->where("aid={$id} and state=1")->select();
		$data=M("cash")->where("aid={$id} and state=2")->select();
		$arr=array();
		foreach ($data as $key => $value) {
			array_push($arr, $value['salesman']);
		}
		if($arr){
			$arrs=M("member")->alias("m")->join("admin a on a.member_id=m.id")->where(array("a.id"=>array('in',$arr)))->Field('m.username,a.id')->select();
			
			foreach ($data as $key => $value) {
				
				foreach ($arrs as $k => $v) {
					if($value['salesman']==$v['id']){
						$data[$key]['admins']=$v['username'];
					}
				}
			}
		}
		
		if($data && empty($dhgl)){
			$_GET['state_id']=25;
			$_GET['lxtime']=time();
			M('applys')->save($_GET);
		}else if($data){
			$_GET['lxtime']=time();
			$_GET['state_id']=24;
			M('applys')->save($_GET);
		}

		$this->assign('data',$data);
		$this->display();
	}


}

 ?>