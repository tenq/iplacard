<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 用户模块
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.0
 */
class User_model extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * 获取用户信息
	 * @param int|string $key ID或邮箱
	 * @param string $part 指定部分
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_user($key, $part = '')
	{
		//判断是否$key查询部分
		if(filter_var($key, FILTER_VALIDATE_EMAIL)) //TODO: FILTER_VALIDATE_EMAIL存在不符合RFC5321情况
			$this->db->where('email', $key);
		else
			$this->db->where('id', intval($key));
		
		$query = $this->db->get('user');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		$data = $query->row_array();
		
		//返回结果
		if(empty($part))
			return $data;
		return $data[$part];
	}
	
	/**
	 * 批量获取用户信息
	 * @param array $ids 用户IDs
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_users($ids = array())
	{
		if(!empty($ids))
			$this->db->where_in('id', $ids);
		$query = $this->db->get('user');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		$return = array();
		
		foreach($query->result_array() as $data)
		{
			$return[$data['id']] = $data;
		}
		$query->free_result();
		
		//返回结果
		return $return;
	}
	
	/**
	 * 查询符合条件的第一个用户ID
	 * @return int|false 符合查询条件的第一个用户ID，如不存在返回FALSE
	 */
	function get_user_id()
	{
		$args = func_get_args();
		array_unshift($args, 'user');
		//将参数传递给get_id方法
		return call_user_func_array(array($this->sql_model, 'get_id'), $args);
	}
	
	/**
	 * 查询符合条件的所有用户ID
	 * @return array|false 符合查询条件的所有用户ID，如不存在返回FALSE
	 */
	function get_user_ids()
	{
		$args = func_get_args();
		array_unshift($args, 'user');
		//将参数传递给get_ids方法
		return call_user_func_array(array($this->sql_model, 'get_ids'), $args);
	}
	
	/**
	 * 编辑/添加用户信息
	 * @return int|false 添加状态新的用户ID，如出错返回FALSE
	 */
	function edit_user($data, $id = '')
	{
		//加密密码
		if(isset($data['password']))
		{
			//获取盐
			if(isset($data['pin_password']))
				$pin = $data['pin_password'];
			elseif(empty($id))
				$pin = option('pin_default_password', 'iPlacard');
			else
				$pin = $this->get_user($id, 'pin_password');
			
			//使用Blowfish算法加密密码
			$data['password'] = $this->_encode_password($data['password'], $pin);
			//加密失败
			if(!$data['password'])
				return false;
		}
		
		//新增用户
		if(empty($id))
		{
			//已存在同邮箱用户
			if($this->user_exists($data['email'], true))
				return false;
			$this->db->insert('user', $data);
			return $this->db->insert_id();
		}
		
		//修改用户
		$this->db->where('id', $id);
		return $this->db->update('user', $data);
	}
	
	/**
	 * 用户登录记录
	 * @return int|false 用户ID，登录错误返回FALSE
	 */
	function login($email, $password, $check = true)
	{
		if($check)
		{
			//登录密码验证
			if(!$this->check_password($email, $password))
				return false;
		}
		
		//获取用户ID
		$id = $this->get_user_id('email', $email);
		
		//记录最后登录信息
		$last = array(
			'last_ip' => $this->input->ip_address(),
			'last_login' => time()
		);
		$this->edit_user($last, $id);
		
		return $id;
	}
	
	/**
	 * 检查用户密码是否正确
	 * @return boolean 密码是否正确
	 */
	function check_password($key, $input_password)
	{
		//获取储存的密码和盐
		$user = $this->get_user($key);
		
		if(!$user)
			return false;
		
		$password = $user['password'];
		$pin = $user['pin_password'];
		
		//验证密码
		if($this->_encode_password($input_password, $pin) == $password)
			return true;
		return false;
	}
	
	/**
	 * 修改密码
	 * @return boolean 修改结果，如crypt错误返回FALSE
	 */
	function change_password($id, $new_password)
	{
		return $this->edit_user(array('password' => $new_password), $id);
	}
	
	/**
	 * 检查用户是否存在
	 * @param int|string $key ID或邮箱
	 * @return boolean
	 */
	function user_exists($key)
	{
		if($this->get_user($key))
			return true;
		return false;
	}
	
	/**
	 * 检查用户是否为管理员
	 * @return boolean
	 */
	function is_admin($id)
	{
		if($this->get_user($id, 'type') == 'admin')
			return true;
		return false;
	}
	
	/**
	 * 检查用户是否为代表
	 * @return boolean
	 */
	function is_delegate($id)
	{
		if($this->get_user($id, 'type') == 'delegate')
			return true;
		return false;
	}
	
	/**
	 * 检查用户帐户是否启用
	 * @return boolean
	 */
	function is_enabled($id)
	{
		return $this->get_user($id, 'enabled') ? true : false;
	}
	
	/**
	 * 根据ID获取用户设置
	 * @param int $id ID
	 * @param string $part 指定部分
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_option($id, $part = '')
	{
		$this->db->where('id', intval($id));
		$query = $this->db->get('user_option');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		$data = $query->row_array();
		$data['value'] = json_decode($data['value'], true);
		
		//返回结果
		if(empty($part))
			return $data;
		return $data[$part];
	}
	
	/**
	 * 获取用户设置
	 * @param string $name 项目
	 * @param mixed $default 默认值，如为空将首先尝试调用系统默认设置
	 * @param int $user 用户ID
	 * @return array|false 值，如不存在返回FALSE
	 */
	function user_option($name, $default = NULL, $user = '')
	{
		//未登录且未指定UID情况下视为系统操作
		if($user == '')
		{
			//未登录用户没有用户设置
			if(!uid())
				return false;
			$user = uid();
		}
		$user = intval($user);
		
		//获取设置
		$this->db->where('user', $user);
		$this->db->where('name', $name);
		$query = $this->db->get('user_option');
		
		//如果设置不存在
		if($query->num_rows() == 0)
		{
			//如果存在默认值
			if(!is_null($default))
				return $default;
			return option("user_option_{$name}");
		}
		
		//返回结果
		$data = $query->row_array();
		return json_decode($data['value'], true);
	}
	
	/**
	 * user_option的同名函数
	 * @param int $user 用户ID
	 * @param string $name 项目
	 * @param mixed $default 默认值，如为空将首先尝试调用系统默认设置
	 * @return array|false 值，如不存在返回FALSE
	 */
	function get_user_option($name, $default = NULL, $user = '')
	{
		return $this->user_option($name, $default, $user);
	}
	
	/**
	 * 查询符合条件的第一个用户设置ID
	 * @return int|false 符合查询条件的第一个用户设置ID，如不存在返回FALSE
	 */
	function get_option_id()
	{
		$args = func_get_args();
		array_unshift($args, 'user_option');
		//将参数传递给get_id方法
		return call_user_func_array(array($this->sql_model, 'get_id'), $args);
	}
	
	/**
	 * 查询符合条件的所有用户设置ID
	 * @return array|false 符合查询条件的所有用户设置ID，如不存在返回FALSE
	 */
	function get_option_ids()
	{
		$args = func_get_args();
		array_unshift($args, 'user_option');
		//将参数传递给get_ids方法
		return call_user_func_array(array($this->sql_model, 'get_ids'), $args);
	}
	
	/**
	 * 编辑/添加用户设置
	 * @param string $name 项目
	 * @param array $value 值
	 * @param int $user 用户ID
	 */
	function edit_user_option($name, $value, $user = '')
	{
		//未登录且未指定UID情况下视为系统操作
		if($user == '')
		{
			//未登录用户没有用户设置
			if(!uid())
				return false;
			$user = uid();
		}
		$user = intval($user);
		
		//检查用户是否存在
		if(!$this->user_exists($user))
			return false;
		
		$value = json_encode($value, JSON_UNESCAPED_UNICODE);
		
		//如不存在项目将添加
		if(!$this->user_option_exists($name, $user))
		{
			$data = array(
				'user' => $user,
				'name' => $name,
				'value' => $value
			);
			return $this->db->insert('user_option', $data);
		}
		
		//更新设置
		$this->db->where('user', $user);
		$this->db->where('name', $name);
		return $this->db->update('user_option', array('value' => $value));
	}
	
	/**
	 * 删除用户设置
	 * @param string $name 项目
	 * @param int $user 用户ID
	 * @return boolean 是否完成删除
	 */
	function delete_user_option($name, $user = '')
	{
		//未登录且未指定UID情况下视为系统操作
		if($user == '')
		{
			//未登录用户没有用户设置
			if(!uid())
				return false;
			$user = uid();
		}
		$user = intval($user);
		
		$this->db->where('user', $user);
		$this->db->where('name', $name);
		return $this->db->delete('user_option');
	}
	
	/**
	 * 检查用户设置是否存在
	 * @param string $name 项目
	 * @param int $user 用户ID
	 */
	function user_option_exists($name, $user = '')
	{
		//未登录且未指定UID情况下视为系统操作
		if($user == '')
		{
			//未登录用户没有用户设置
			if(!uid())
				return false;
			$user = uid();
		}
		$user = intval($user);
		
		//获取设置
		$this->db->where('user', $user);
		$this->db->where('name', $name);
		$query = $this->db->get('user_option');
		
		//如果设置不存在
		if($query->num_rows() == 0)
			return false;
		return true;
	}
	
	/**
	 * 获取用户消息
	 * @param int $id 消息ID
	 * @param string $part 指定部分
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_message($id, $part = '')
	{
		$this->db->where('id', $id);
		$query = $this->db->get('message');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		$data = $query->row_array();
		
		//返回结果
		if(empty($part))
			return $data;
		return $data[$part];
	}
	
	/**
	 * 批量获取用户消息
	 * @param array $ids 消息IDs
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_messages($ids = array())
	{
		if(!empty($ids))
			$this->db->where_in('id', $ids);
		$query = $this->db->get('message');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		$return = array();
		
		foreach($query->result_array() as $data)
		{
			$return[$data['id']] = $data;
		}
		$query->free_result();
		
		//返回结果
		return $return;
	}
	
	/**
	 * 查询符合条件的第一个消息ID
	 * @return int|false 符合查询条件的第一个消息ID，如不存在返回FALSE
	 */
	function get_message_id()
	{
		$args = func_get_args();
		array_unshift($args, 'message');
		//将参数传递给get_id方法
		return call_user_func_array(array($this->sql_model, 'get_id'), $args);
	}
	
	/**
	 * 查询符合条件的所有消息ID
	 * @return array|false 符合查询条件的所有消息ID，如不存在返回FALSE
	 */
	function get_message_ids()
	{
		$args = func_get_args();
		array_unshift($args, 'message');
		//将参数传递给get_ids方法
		return call_user_func_array(array($this->sql_model, 'get_ids'), $args);
	}
	
	/**
	 * 获取指定用户的所有消息
	 */
	function get_user_messages($user)
	{
		return $this->get_message_ids('receiver', $user);
	}
	
	/**
	 * 编辑/添加用户消息
	 * @return int 新的消息ID
	 */
	function edit_message($data, $id = '')
	{
		//新增消息
		if(empty($id))
		{
			$this->db->insert('message', $data);
			return $this->db->insert_id();
		}
		
		//更新消息
		$this->db->where('id', $id);
		return $this->db->update('message', $data);
	}
	
	/**
	 * 更新用户消息状态
	 */
	function update_message_status($id, $status)
	{
		$available = array(
			'unread',
			'read',
			'archived'
		);
		
		if(!in_array($status, $available))
			return false;
		
		return $this->edit_message(array('status' => $status), $id);
	}
	
	/**
	 * 添加用户消息
	 * @return int 新的用户消息ID
	 */
	function add_message($receiver, $text, $type = 'system', $sender = 0)
	{
		$data = array(
			'sender' => $sender,
			'receiver' => $receiver,
			'text' => $text,
			'type' => $type,
			'time' => time(),
			'status' => 'unread'
		);
		
		//返回新消息ID
		return $this->edit_message($data);
	}
	
	/**
	 * 使用Blowfish算法加密密码
	 * @param string $password 密码
	 * @param string $salt 盐
	 * @return string|false 密文，crypt出错返回FALSE
	 */
	private function _encode_password($password, $salt)
	{
		$encoded_string = crypt(sha1($password), '$2a$12$'.sha1($salt).'$');
		
		//检查crypt加密是否出错
		if(!$encoded_string)
			return false;
		
		return $encoded_string;
	}
}

/* End of file user_model.php */
/* Location: ./application/models/user_model.php */