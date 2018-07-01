<?php
require_once "WxPayApi.php";
require_once 'WxPayNotify.php';
require_once 'Log.php';

class PayNotifyCallBack extends WxPayNotify
{
	//查询订单
	public function Queryorder($transaction_id)
	{
		$input = new WxPayOrderQuery();
		$input->SetTransaction_id($transaction_id);
		$result = WxPayApi::orderQuery($input);
		Log::DEBUG("query:" . json_encode($result));
		if(array_key_exists("return_code", $result)
			&& array_key_exists("result_code", $result)
			&& $result["return_code"] == "SUCCESS"
			&& $result["result_code"] == "SUCCESS")
		{
			return true;
		}
		return false;
	}
	
	//重写回调处理函数
	public function NotifyProcess($data, &$msg)
	{
		Log::DEBUG("call back:" . json_encode($data));
		$notfiyOutput = array();
		
		if(!array_key_exists("transaction_id", $data)){
			$msg = "输入参数不正确";
			return false;
		}
		//查询订单，判断订单真实性
		if(!$this->Queryorder($data["transaction_id"])){
			$msg = "订单查询失败";
			return false;
		}
		//更新订单状态
		if($data['result_code'] == 'SUCCESS' && $data['return_code'] == 'SUCCESS') {
			//$order_id = $data['transaction_id'];
			$order_id = $data['out_trade_no'];
			$order = D('user_order')->where('order_id="'.$order_id.'"')->find();
			if($order && $order['status'] == 0) {
				D('user_order')->where('order_id="'.$order_id.'"')->save(array('status' => 1));
				//处理问答订单
				if($order['type'] == 1) {
					//更新订单号
	                D('user_ask')->where('id='.$order['ask_id'])->setField(array('order_id' => $order_id)); 
				}
				//偷听处理订单
				if($order['type'] == 2) {
					D('user_touting')->where('ask_id='.$order['ask_id'].' and uid='.$order['uid'])->save(array('status' => 1));
					//增加偷听数
	                D('user_ask')->where('id='.$order['ask_id'])->setField(array('touting' => array('exp', "(touting + 1)"))); 
				}
			}
		}

		return true;
	}
}