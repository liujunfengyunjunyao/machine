<?php
namespace Admin\Model;
use Think\Model;
class GoodsModel extends Model{
	public function upload_logo($file,&$data){
		//判断文件有没有上传
			if (!isset($file)||$file['error'] != 0) {
				//将错误信息保存到 模型的  error属性中
				$this-> error='图片上传失败';
				return false;
			}

			// dump($file);die;
			//使用Upload类上传文件
			//自定义配置数组 实例化Upload类
			$config=array(
					'maxSize' =>  5*1014*1024, //上传的文件大小限制 (0-不做限制)
					'exts'    =>  array('jpg','jpeg','png','gif'), //允许上传的文件后缀
					'rootPath' =>  ROOT_PATH.UPLOAD_PATH, //保存根路径
				);

			//实例化上传类
			$upload= new \Think\Upload($config);

			//使用uploadOne方法实现上传
			$upload_res=$upload->uploadOne($file);
			// $error = $upload -> getError();
			// echo $error;
			// dump($upload_res);
			//判断上传是否成功
			if (!$upload_res) {
				$error=$upload->getError();
				$this-> error=$error;
				return false;
			}

			// dump($upload_res);die;
			//上传成功后保存到数据库目录
			$data['equipment_big_img']=UPLOAD_PATH.$upload_res['savepath'].$upload_res['savename'];

			//生成缩略图
			$img=new \Think\Image();

			//打开原始图片
			$img->open(ROOT_PATH.$data['equipment_big_img']);

			//生成缩略图
			$img->thumb(120,120);

			//缩略图数据库保存地址
			$equipment_small_img=UPLOAD_PATH.$upload_res['savepath'].'thumb_'.$upload_res['savename'];

			//保存本地目录
			$img->save(ROOT_PATH.$equipment_small_img);

			$data['equipment_small_img']=$equipment_small_img;

			return true;
	}

	//多文件上传
	public function upload_pics($goods_id,$files){
		//判断是否有文件上传
		if (!isset($files['goods_pics'])||min($files['goods_pics']['error']) !=0) {
			return false;
		}

		//使用Upload类上传文件
		//自定义配置数组
		$config=array(
				'maxSize' =>  5*1014*1024, //上传的文件大小限制 (0-不做限制)
				'exts'    =>  array('jpg','jpeg','png','gif'), //允许上传的文件后缀
				'rootPath' =>  ROOT_PATH.UPLOAD_PATH, //保存根路径
		);
		//图片上传
		//实例化上传类
		$upload = new \Think\Upload($config);
        //调用upload方法实现多文件上传
        $res_upload = $upload -> upload($files);
        //上传的图片路径
		//判断上传后的结果
		if (!$res_upload) {
			//上传失败返回 false
			return false;
		}

		//遍历数组 生成 不同大小的缩略图
		foreach ($res_upload as $k => $v) {
			//把每一个上传文件 保存到项目目录
			$origin=UPLOAD_PATH.$v['savepath'].$v['savename'];

			//实例化 Image图片类
			$image= new \Think\Image();

			//打开原文件 路径(绝对路径)
			$image->open(ROOT_PATH.$origin);
			// //设置1024图片的大小
			// $image->thumb(1024,1024);
			// $pics_max=UPLOAD_PATH.$v['savepath'].'thumb_1024'.$v['savename'];
			// $image->save(ROOT_PATH.$pics_max); 
			// //设置500图片的大小
			// $image->thumb(500,500);
			// //保存缩略图到项目目录(绝对路径
			// $pics_big=UPLOAD_PATH.$v['savepath'].'thumb500_'.$v['savename'];
			// $image->save(ROOT_PATH.$pics_big);

			//设置200图片的大小
			$image->thumb(200,200);
			//保存缩略图到项目目录(绝对路径
			$pics_mid=UPLOAD_PATH.$v['savepath'].'thumb200_'.$v['savename'];
			$image->save(ROOT_PATH.$pics_mid);

			// //设置50图片的大小
			// $image->thumb(50,50);
			// //保存缩略图到项目目录(绝对路径
			// $pics_sma=UPLOAD_PATH.$v['savepath'].'thumb50_'.$v['savename'];
			// $image->save(ROOT_PATH.$pics_sma);
			
			//将图片添加到GOODS表
			$row=array(
				'goods_id' => $goods_id,
				'pics_origin' => $origin,
				
				
				'pics_mid' => $pics_mid,
			
			);
			D('Goodspics')->add($row);
		}


			//实例化 Goodspics 模型类 关联 Goodspics 数据表 完成添加数据
			
			//设置缩略图成功 返回 结果
			return true;

	}

	//删除商品相册图片
	public function clear($pic_info){
			unlink(ROOT_PATH.$pic_info['pics_origin']);
			unlink(ROOT_PATH.$pic_info['pics_big']);
			unlink(ROOT_PATH.$pic_info['pics_sma']);
			unlink(ROOT_PATH.$pic_info['pics_mid']);
	}
}