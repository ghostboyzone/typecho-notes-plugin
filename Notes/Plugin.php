<?php
/**
 * 便签插件
 * 
 * @package Notes
 * @author GhostBoyZone
 * @version 0.0.1
 * @link http://66e.in
 */
class Notes_Plugin implements Typecho_Plugin_Interface 
{

	/**
	 * 激活插件方法,如果激活失败,直接抛出异常
	 * 
	 * @access public
	 * @return void
	 * @throws Typecho_Plugin_Exception
	 */
	public static function activate()
	{
		$msg = self::install();
		// Helper::addPanel(2, 'Notes/add-note.php', '写便签', '新建便签', 'administrator');
		Helper::addPanel(3, 'Notes/manage-notes.php', '便签', '管理便签', 'administrator');
	    // Typecho_Plugin::factory('admin/menu.php')->navBar = array('HelloWorld_Plugin', 'render');
		Helper::addAction('notes-manage', 'Notes_Action');
		Helper::addRoute('share_note', '/share_note/[key]/', 'Notes_Action', 'shareNote');

	    return _t($meg);
	}
	
	/**
	 * 禁用插件方法,如果禁用失败,直接抛出异常
	 * 
	 * @static
	 * @access public
	 * @return void
	 * @throws Typecho_Plugin_Exception
	 */
	public static function deactivate(){
		Helper::removeRoute('share_note');
		Helper::removeAction('notes-manage');
		// Helper::removePanel(2, 'Notes/add-note.php');
		Helper::removePanel(3, 'Notes/manage-notes.php');

		self::uninstall();
	}
	
	/**
	 * 获取插件配置面板
	 * 
	 * @access public
	 * @param Typecho_Widget_Helper_Form $form 配置面板
	 * @return void
	 */
	public static function config(Typecho_Widget_Helper_Form $form)
	{
	    $pageSize = new Typecho_Widget_Helper_Form_Element_Text(
	        'pageSize', null, 20,
	        '分页数量', '每页显示的便签数量');
	    $isDrop = new Typecho_Widget_Helper_Form_Element_Radio(
	        'isDrop', array(
	            '0' => '删除',
	            '1' => '不删除',
	        ), 1, '删除数据表:', '请选择是否在禁用插件时，删除日志数据表');
	    $form->addInput($pageSize);
	    $form->addInput($isDrop);

	    // Typecho_Widget::widget('Widget_Options')->plugin('Notes')->pageSize
	}
	
	/**
	 * 个人用户的配置面板
	 * 
	 * @access public
	 * @param Typecho_Widget_Helper_Form $form
	 * @return void
	 */
	public static function personalConfig(Typecho_Widget_Helper_Form $form){}
	
	/**
	 * 插件实现方法
	 * 
	 * @access public
	 * @return void
	 */
	public static function render()
	{
	    echo '<span class="message success">'
	        . htmlspecialchars(Typecho_Widget::widget('Widget_Options')->plugin('Notes')->pageSize)
	        . '</span>';
	}

	public static function install()
	{
		$installDb = Typecho_Db::get();
		$type = explode('_', $installDb->getAdapterName());
		$type = array_pop($type);
		$prefix = $installDb->getPrefix();
		$scripts = file_get_contents('usr/plugins/Notes/Mysql.sql');
		$scripts = str_replace('typecho_', $prefix, $scripts);
		$scripts = str_replace('%charset%', 'utf8', $scripts);
		$scripts = explode(';', $scripts);
		try {
			foreach ($scripts as $script) {
				$script = trim($script);
				if ($script) {
					$installDb->query($script, Typecho_Db::WRITE);
				}
			}
			return '成功创建数据表，插件启用成功';
		} catch (Typecho_Db_Exception $e) {
			$code = $e->getCode();
			if(('Mysql' == $type && 1050 == $code)) {
					$script = 'SELECT * from `' . $prefix . 'notes`';
					$installDb->query($script, Typecho_Db::READ);
					return '数据表已存在，插件启用成功';	
			} else {
				throw new Typecho_Plugin_Exception('数据表建立失败，插件启用失败。错误号：'.$code);
			}
		}	
	}

	public static function uninstall()
	{
		$installDb = Typecho_Db::get();
		$type = explode('_', $installDb->getAdapterName());
		$type = array_pop($type);
		$prefix = $installDb->getPrefix();

		try {
			$script = 'drop table `' . $prefix . 'notes`';
			$installDb->query($script, Typecho_Db::WRITE);
		} catch (Typecho_Db_Exception $e) {
		}
	}
}