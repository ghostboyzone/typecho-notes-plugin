<?php

class Notes_Action extends Widget_Abstract_Contents implements Widget_Interface_Do
{

	/**
	 * 错误信息输出
	 *
	 * @author mawenhao
	 * @version [version]
	 * @date    2016-01-20
	 * @param   string     $message [description]
	 * @param   array      $data    [description]
	 * @return  [type]              [description]
	 */
	private function error($message = '', $data = array())
	{
		$this->response->throwJson(array(
			'result' => false,
			'message' => $message,
			'data' => $data
		));
	}

	/**
	 * 成功返回
	 *
	 * @author mawenhao
	 * @version [version]
	 * @date    2016-01-20
	 * @param   array      $data    [description]
	 * @param   string     $message [description]
	 * @return  [type]              [description]
	 */
	private function success($data = array(), $message = '')
	{
		$this->response->throwJson(array(
			'result' => true,
			'message' => $message,
			'data' => $data
		));
	}

	/**
	 * 处理参数
	 *
	 * @author mawenhao
	 * @version [version]
	 * @date    2016-01-20
	 * @param   array      $params [description]
	 * @return  [type]             [description]
	 */
	private function filterParams($params = array())
	{
		$data = array();
		$data['title'] = isset($params['title']) ? $params['title'] : '未命名便签';
		$data['content'] = isset($params['content']) ? $params['content'] : '';
		$data['authorId'] = isset($params['authorId']) ? $params['authorId'] : 0;

		if($data['authorId'] <= 0){
			$this->error('请登录后台后重试');
		}

		$date = (new Typecho_Date($this->options->gmtTime))->format('Y-m-d H:i:s');

		$data['created_at'] = $date;
		$data['updated_at'] = $date;
		return $data;
	}

	/**
	 * 新建便签
	 *
	 * @author mawenhao
	 * @version [version]
	 * @date    2016-01-20
	 */
	public function addNote()
	{
		if(!$this->user->hasLogin()){
			$this->error('请登录后台后重试');
		}

		$data = array(
			'title' => $this->request->get('title', '未命名便签'),
			'content' => $this->request->get('content', ''),
			'authorId' => $this->user->uid,
		);

		$noteId = $this->db->query($this->db->insert('table.notes')->rows($this->filterParams($data)));

		$data = $this->db->fetchRow($this->db->select('table.notes.*, table.users.name author_name')->from('table.notes')->join('table.users', 'table.notes.authorId = table.users.uid')->where('table.notes.id =  ?', $noteId));
		
		$this->success($data);
	}

	/**
	 * 保存便签
	 *
	 * @author mawenhao
	 * @version [version]
	 * @date    2016-01-21
	 * @return  [type]     [description]
	 */
	public function saveNote()
	{
		if(!$this->user->hasLogin()){
			$this->error('请登录后台后重试');
		}

		$noteId = $this->request->get('id', 0);

		$data = array(
			'title' => $this->request->get('title', '未命名便签'),
			'content' => $this->request->get('content', ''),
		);

		$this->db->query($this->db->update('table.notes')->rows($data)->where('id = ?', $noteId));

		$data = $this->db->fetchRow($this->db->select('table.notes.*, table.users.name author_name')->from('table.notes')->join('table.users', 'table.notes.authorId = table.users.uid')->where('table.notes.id =  ?', $noteId));
		
		$this->success($data);
	}

	/**
	 * 展示便签
	 *
	 * @author mawenhao
	 * @version [version]
	 * @date    2016-01-21
	 * @return  [type]     [description]
	 */
	public function listNotes()
	{
		if(!$this->user->hasLogin()){
			$this->error('请登录后台后重试');
		}

		$lastid = $this->request->get('lastid', 0);
		$size = 10;

		if($lastid){
			$data = $this->db->fetchAll($this->db->select('table.notes.*, table.users.name author_name')->from('table.notes')->join('table.users', 'table.notes.authorId = table.users.uid')->where('table.notes.id < ? ', $lastid)->order('table.notes.id', Typecho_Db::SORT_DESC)->limit($size));
		}
		else{
			$data = $this->db->fetchAll($this->db->select('table.notes.*, table.users.name author_name')->from('table.notes')->join('table.users', 'table.notes.authorId = table.users.uid')->order('table.notes.id', Typecho_Db::SORT_DESC)->limit($size));
		}

		$this->success($data);


		// foreach($data as $k => $v){
		// 	$data[$k]['author'] = $this->db->fetchRow($this->db->select('*')->from('table.users')->where('id > ? ', $lastid)->page($page, $size))
		// }


		// dd($this->db->fetchAll($this->db->select('*')->from('table.notes')->where('id > ? ', $lastid)->page($page, $size)));

		// dd($this->request->get('lastid', 0));

		// $size = $this->request->get('size', 10);


		// $row = $this->db->fetchRow($this->db->select('*')->from('table.notes'));
		// $this->response->throwJson(['aa'=>1]);
		// dd($row);
	}

	/**
	 * 删除便签
	 *
	 * @author mawenhao
	 * @version [version]
	 * @date    2016-01-21
	 * @return  [type]     [description]
	 */
	public function deleteNote()
	{
		if(!$this->user->hasLogin()){
			$this->error('请登录后台后重试');
		}

		$id = $this->request->get('id', 0);

		if(!$id){
			$this->success();
		}

		$this->db->query($this->db->delete('table.notes')
		            ->where('id = ?', $id));

		$this->success();
	}

	public function shareNote()
	{
		echo $this->request->get('key');
	}

	public function action()
	{
		$this->db = Typecho_Db::get();
		$this->prefix = $this->db->getPrefix();
		$this->options = Typecho_Widget::widget('Widget_Options');
		$this->on($this->request->is('do=addnote'))->addNote();
		$this->on($this->request->is('do=savenote'))->saveNote();
		$this->on($this->request->is('do=listnotes'))->listNotes();
		$this->on($this->request->is('do=deletenote'))->deleteNote();
		// $this->on($this->request->is('do=sharenote'))->shareNote();

		// $this->response->redirect($this->options->adminUrl);
		$this->response->redirect(Typecho_Common::url('extending.php?panel=Notes%2Fmanage-notes.php', $this->options->adminUrl));
	}
}