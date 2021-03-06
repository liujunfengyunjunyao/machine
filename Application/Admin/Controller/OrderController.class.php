<?php
namespace Admin\Controller;
use Think\Controller;
class OrderController extends CommonController{
	
	public function log_index(){
		$id = session('manager_info.id');
		$equipment_ids = implode(',',D('Equipment')->where(['pid'=>$id])->getField('id',true));//查询出机台ids集合用作查找game_log
		//var_dump($equipment_ids);
		$owner = M('Manager')->where("pid = $id")->getField('id',true);
		$owner = implode(',',$owner);
		//查询出抓取到娃娃的订单信息
		if($owner){
		// $data = M('tbl_order')
		// ->alias('t1')
		// ->distinct(true)
		// ->field("t1.id,t1.address,t1.phone,t1.name,t1.create_time,t1.userid,t3.name as goods_name,t1.status,
		// t2.start_time,t2.end_time,t2.id as log_id,t1.name as address_name,t4.name as equipment_name,
		// t4.id as equipment_id,t3.id as goods_id")
		// ->where("t1.status = 0 && (t4.pid = $id || t4.pid in ({$owner}))")
		// ->join("left join tbl_game_log as t2 on t1.log_id = t2.id")
		// ->join("left join goods as t3 on t3.id = t2.goods_id")
		// ->join("equipment as t4 on t4.id = t2.equipment_id")
		// ->select();
		// $tbl_order = M("tbl_order")->where('status = 0')->getField("log_id",true);
		// $tbl_order = implode(',',$tbl_order);
		// $data = M("tbl_game_log")->alias('t1')
		// ->field("t1.start_time,t1.end_time,t1.id as log_id,t2.status,t2.id,t2.log_id,t2.address,t2.name as address_name,t2.phone,t3.name as goods_name,
		// t4.name as equipment_name,t4.id as equipment_id,t3.id as goods_id")
		// ->where("t1.id in ($tbl_order) && t2.address !='' && t2.id !='' && t2.phone != '' && t2.name != '' && t2.log_id != ''")
		// ->join("left join tbl_order as t2 on t2.log_id = t1.id")
		// ->join("left join goods as t3 on t3.id = t1.goods_id")
		// ->join("equipment as t4 on t4.id = t1.equipment_id")
		// ->select();
		$order = M("tbl_order")->alias('t1')->field("t1.id,t1.address,t1.phone,t1.name as address_name,t1.status,t2.start_time,t2.end_time,t1.log_id")
		->where("t1.status = 0 && (t3.pid = $id || t3.pid in ({$owner}))")
		->join("left join tbl_game_log as t2 on t2.id = t1.log_id")
		->join("equipment as t3 on t3.id = t2.equipment_id")
		->select();
		//dump($order);die;
		foreach ($order as $key => $value) {
			$data[$key]['id'] = $value['id'];
			$data[$key]['address'] = $value['address'];
			$data[$key]['phone'] = $value['phone'];
			$data[$key]['address_name'] =  $value['address_name'];
			$data[$key]['status'] = $value['status'];
			$data[$key]['start_time'] = $value['start_time'];
			$data[$key]['end_time'] = $value['end_time'];
			$data[$key]['log_id'] = $value['log_id'];
			$a = $value['log_id'];
			$data[$key]['goods_name'] = implode(M('tbl_game_log')->alias('t1')->where(['t1.id'=>['in',$a]])->join("left join goods as t2 on t2.id = t1.goods_id")->getField('name',true),',');
			$data[$key]['goods_id']= implode(M('tbl_game_log')->where(['id'=> ['in',$a]])->getField('goods_id',true),',');
			$data[$key]['equipment_id'] = implode(M("tbl_game_log")->where(['id'=>['in',$a]])->getField('equipment_id',true),',');
			$data[$key]['equipment_name'] = implode(M('tbl_game_log')->alias('t1')->where(['t1.id'=>['in',$a]])->join("left join equipment as t2 on t2.id = t1.equipment_id")->getField('name',true),',');
		}
	   
	    }else{
	    $order = M("tbl_order")->alias('t1')->field("t1.id,t1.address,t1.phone,t1.name as address_name,t1.status,t2.start_time,t2.end_time,t1.log_id")
		->where("t1.status = 0 && t3.pid = $id")
		->join("left join tbl_game_log as t2 on t2.id = t1.log_id")
		->join("equipment as t3 on t3.id = t2.equipment_id")
		->select();
		//dump($order);die;
				foreach ($order as $key => $value) {
					$data[$key]['id'] = $value['id'];
					$data[$key]['address'] = $value['address'];
					$data[$key]['phone'] = $value['phone'];
					$data[$key]['address_name'] =  $value['address_name'];
					$data[$key]['status'] = $value['status'];
					$data[$key]['start_time'] = $value['start_time'];
					$data[$key]['end_time'] = $value['end_time'];
					$data[$key]['log_id'] = $value['log_id'];
					$a = $value['log_id'];
					$data[$key]['goods_name'] = implode(M('tbl_game_log')->alias('t1')->where(['t1.id'=>['in',$a]])->join("left join goods as t2 on t2.id = t1.goods_id")->getField('name',true),',');
					$data[$key]['goods_id']= implode(M('tbl_game_log')->where(['id'=> ['in',$a]])->getField('goods_id',true),',');
					$data[$key]['equipment_id'] = implode(M("tbl_game_log")->where(['id'=>['in',$a]])->getField('equipment_id',true),',');
					$data[$key]['equipment_name'] = implode(M('tbl_game_log')->alias('t1')->where(['t1.id'=>['in',$a]])->join("left join equipment as t2 on t2.id = t1.equipment_id")->getField('name',true),',');

			    }
	   }
	   //dump($data);die;
		$row_count = count($data);
		$this->assign('row_count',$row_count);
		$this->assign('data',$data);
		$this->display();
	}
	//添加物流页面
	public function order_add(){
		if (IS_POST) {
			//将这个订单添加到物流表里
			$data = I('post.');	
			//var_dump($data);die;
			//添加物流表的创建时间
			$data['send_time'] = time();
			$data['status'] = 1;//status为0的时候客户端可以通过接口修改物流信息
			//将物流表中的status状态修改为1 (不允许客户端修改已经发货)
			//在前端页面获取到log_id 完成添加
			// $res = D('tbl_order')->add($data);
			$res = M('tbl_order')->where(['log_id'=>$data['log_id']])->save($data);

			if ($res!==false) {
			
				$this->success('物流创建完成',U('Admin/Order/log_index'));
			}else{
				$this->error('创建失败,请稍后再试');
			}
		}else{
			
			$log_id = I('get.id');
			//var_dump($log_id);die;
			$data = M('tbl_order')->alias("t1")->where(['log_id'=>$log_id])->join("left join tbl_game_log as t2 on t2.id = t1.log_id")->find();
			$express = D('Express')->select();
			$this->assign('data',$data);
			$this->assign('express',$express);
			$this->assign('log_id',$log_id);
			//添加订单页面
			$this->display();
		}
	}

	//修改物流信息
	public function order_edit(){
		if (IS_POST) {
			$data = I('post.');
			//var_dump($data);die;
			$id = I('post.id');
			$res = D('tbl_order')->where(['id'=>$id])->save($data);
			if ($res!==false) {
				$this->success('修改成功',U('Admin/Order/order_index'));
			}else{
				$this->error('修改失败');
			}
			
		}else{
			$id = I('get.id');
			$data = D('tbl_order')->where(['id'=>$id])->find();
			
			// $express = D('Express')->where(['express_id'=>$data['express_id']])->find();
			$express = D('Express')->select();
			
			$this->assign('express',$express);
			$this->assign('data',$data);
			$this->display();
		}
	}
	//修改订单页面
	// public function log_edit(){
	// 	if (IS_POST) {
			
	// 	}else{
	// 		$id = I('get.id');
			
	// 		$this->display();
	// 	}
	// }

	//物流列表首页
	public function order_index(){
		$id = session('manager_info.id');
		//查询出这个管理员拥有的机台的ids集合
		// $equipment_ids = implode(',',D('Equipment')->where(['equipment_pid'=>$id])->getField('id',true));
		//t1为物流表,t2为订单表,t3为机台表,t4为商品表
		//  $data = D('tbl_order')
		// ->alias("t1")
		// ->field("t1.*,t4.name as goods_name,t4.id as goods_id,t2.id as game_log_id")
		// ->where(['t3.pid'=>$id,'t1.status'=>1])
		// ->join("left join tbl_game_log as t2 on t1.log_id = t2.id")
		// ->join("left join equipment as t3 on t3.id = t2.equipment_id")
		// ->join("left join goods as t4 on t2.goods_id = t4.id")
		// ->select();
		// dump($data);
		$tbl_order = D('tbl_order')
		->alias('t1')
		->field("t1.*")
		->where(['t3.pid'=>$id,'t1.status'=>1])
		->join("left join tbl_game_log as t2 on t2.id = t1.log_id")
		->join("left join equipment as t3 on t3.id = t2.equipment_id")
		->select();
		//dump($tbl_order);die;
		foreach ($tbl_order as $key => $value) {
				$data[$key]["id"] = $value['id'];
			    $data[$key]["log_id"] = $value['log_id'];
			    $a = $value['log_id'];
			    $data[$key]["name"] = $value['name']; 
			    $data[$key]["create_time"] = $value['create_time']; 
			    $data[$key]["address"] = $value['address']; 
			    $data[$key]["phone"] = $value['phone'];
			    $data[$key]["send_time"] = $value['send_time']; 
			    $data[$key]["express_id"] = $value['express_id']; 
			    $data[$key]["trace_number"] = $value['trace_number']; 
			    $data[$key]["status"] = $value['status']; 
			    $data[$key]["userid"] = $value['userid'];
			    $data[$key]['goods_id']= implode(M('tbl_game_log')->where(['id'=> ['in',$a]])->getField('goods_id',true),',');
			    $data[$key]["goods_name"] = implode(M("tbl_game_log")->alias('t1')->where(['t1.id'=>['in',$a]])->join('left join goods as t2 on t2.id=t1.goods_id')->getField('name',true),',');
		}
		//dump($data);die;
		// $data = M('tbl_order')->alias("t1")->field("t1.*,t4.name as goods_name,t4.id as goods_id")->where("t3.pid = $id and t1.status = 1")->join("left join tbl_game_log as t2 on t1.log_id = t2.id")->join("left join equipment as t3 on t3.id = t2.equipment_id")->join("left join goods as t4 . t2.goods_id = t4.id")->select();
		
		$row_count = count($data);		
		$this->assign('row_count',$row_count);
		$this->assign('data',$data);
		$this->display();
	}


	//删除订单
	public function log_del(){
		$id = I('get.id');
		$log = D("tbl_order")->where(['id'=>$id])->find();
		$log_id = $log['id'];
		$res = D('tbl_order')->where(['id'=>$id])->delete();
		if ($res!==false) {
			//删除成功
			$description = '删除了订单:' . '    '. $log_id;
			$this->operate_log($description);
			$this->success('删除成功',U('Admin/Order/log_index'));
		}else{
			//删除失败
			$this->error('删除失败');
		}

	}



	//删除物流
	public function order_del(){
		$id = I('get.id');
		$order = D('tbl_order')->where(['id'=>$id])->find();
		$order_id = $order['id'];
		$res = D('tbl_order')->where(['id'=>$id])->delete();
		if ($res!==false) {
			//删除成功
			$description = '删除了物流信息' . '    ' . $order_id;
			$this->operate_log($description);
			$this->success('删除成功',U('Admin/Order/order_index'));
		}else{
			//删除失败
			$this->error('删除失败');
		}
	}


}

