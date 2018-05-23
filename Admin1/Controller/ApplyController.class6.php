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
		$udata['username']=$_POST['username'];
		$udata['sex']=$_POST['sex'];
		$udata['phone']=$_POST['phone'];
		$udata['loan']=$_POST['loan']*10000;
		$udata['addtime']=time();
		$udata['state_id']=17;
		$udata['use']=$_POST['use'];
		$udata['admin_ids']=$_POST['admin_ids'];
		$udata['admin_id']=$_POST['admin_id'];
		$array=M("admin")->where(array("id"=>$_POST['admin_id']))->field('member_id')->find();
		$udata['member_id']=$array['member_id'];
		$id=M("Uclient")->add($udata);
		if($id){
		
			if(!empty($_FILES)){
				$upload=new \Think\Upload();
				$upload->maxSize   =     10000000;
				$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');
		    	$upload->rootPath  =     './Uploads/'; 
		   		$upload->savePath  =     "Files_photo/{$id}/";
			}
			$info = $upload->upload();
			if(!$info) {  
				    $this->error($upload->getError());  
				}else{
				    foreach($info as $file){  
				        $adata['photo'] = './Uploads/'.$file['savepath'].$file['savename'];  
				    }  
				$adata['uid']=$id;
				$adata['cardId']=$_POST['cardId'];
				$adata['maritalstatus']=$_POST['maritalstatus'];
				$adata['children']=$_POST['children'];
				$adata['cometime']=$_POST['cometime'];
				$adata['address']=$_POST['address'];
				$adata['company']=$_POST['company'];
				$adata['numpeople']=$_POST['numpeople'];
				$adata['department']=$_POST['department'];
				$adata['workhours']=$_POST['workhours'];
				$adata['telphone']=$_POST['telphone'];
				$adata['companyaddress']=$_POST['companyaddress'];
				$adata['payday']=$_POST['payday'];
				$adata['peioname']=$_POST['peioname'];
				$adata['peiotel']=$_POST['peiotel'];
				$adata['peiocompany']=$_POST['peiocompany'];
				$adata['peioaddress']=$_POST['peioaddress'];
				$adata['relativesname']=$_POST['relativesname'];
				$adata['relativestel']=$_POST['relativestel'];
				$adata['relatives']=$_POST['relatives'];
				$adata['relativesaddres']=$_POST['relativesaddres'];
				$adata['colleaguename']=$_POST['colleaguename'];
				$adata['colleaguetel']=$_POST['colleaguetel'];
				$adata['colleaguedwtel']=$_POST['colleaguedwtel'];
				$adata['colleaguebm']=$_POST['colleaguebm'];
				$adata['colleaguezw']=$_POST['colleaguezw'];
				$adata['friendname']=$_POST['friendname'];
				$adata['friendtel']=$_POST['friendtel'];
				$ids=M("applys")->add($adata);
				if($ids>0){
					echo "<script>alert('添加资料成功')</script>";
					$this->success('页面即将跳转...','http://47.92.119.237:8888/Signing/');
				}else{
					M("Uclient")->delete($id);
					echo "<script>alert('添加资料失败')</script>";
					$this->error('页面即将跳转...', U('Apply/index'));
				}
			} 
		}else{
			echo "<script>alert('添加资料失败')</script>";
			$this->error('页面即将跳转...', U('Apply/index'));
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
		unset($data['photo']);
		$id=I('post.id');
		$data['loan']=$data['loan']*10000;
		// var_dump($data);exit();
		$num=M('Uclient')->where("id={$id}")->save($data);
		$datas=$data;
		unset($datas['id']);

		$nums=M("Applys")->where("uid={$id}")->save($datas);
		if($num>0 || $nums>0){
			$this->success('修改成功...', U('/Admin/Monetary/listCustomer'));
		}else{
			$this->error('修改失败...', U('/Admin/Monetary/listCustomer'));
		}
	}

}

 ?>