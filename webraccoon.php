<?php
/*
**************************************************************************************
*File Manager Web Raccoon
*Author:     Dima Boyko
*EMAil:      dimatmg@gmail.com
**************************************************************************************
*/



class WebRaccoon
{

	static public $varsion='0.1';
	static public $root='';
	static public $path='';
	static public $back='';
	static public $view='';
	static public $max_file_size=10000000000;//byte
	static public $user='';
	static public $users=Array(
		'admin'=>'rt#83u',
		'user'=>'usr@528'
	);

	static public function Start(){
		self::Controller();
		self::Template();
	}

	static public function Controller(){
		if(!self::Authorization())return false;
		self::$view='fm';
		self::$root=$_SERVER['DOCUMENT_ROOT'];
		if(self::IGet('path')){
			$path=self::LGet('path');
			if(file_exists(self::$root.'/'.$path))self::$path=$path;
		}
		self::$back=substr(self::$path,0,strripos(self::$path,'/'));
		if(self::IGet('edit'))self::$view='edit';

		self::Save();
		self::CreateFolder();
		self::Download();
		self::Upload();
		self::Rename();
		self::Zip();
		self::Unzip();
		self::Delete();

	}

	static public function IGet($key=''){
		return isset($_GET[$key]);
	}

	static public function LGet($key='',$default=''){
		if(!isset($_GET[$key]))return $default;
		return $_GET[$key];
	}

	static public function IPost($key=''){
		return isset($_POST[$key]);
	}

	static public function LPost($key='',$default=''){
		if(!isset($_POST[$key]))return $default;
		return $_POST[$key];
	}

	static public function Redirect($URL=""){
		if(empty($URL))$URL=$_SERVER['REQUEST_URI'];
		header("Location: ".$URL);
		exit;
	}


	static public function getFilePath($file_name=''){
		$path=self::$path;
		$path.='/';
		return self::$root.$path.$file_name;
	}

	static public function Authorization(){
		if(self::LGet('logout')=='y'){
			SetCookie("wrc_user",'',time());  
			SetCookie("wrc_password",'',time());
			self::Redirect('?'); 
		}
		if(isset($_POST['frm-login'])){
			if(empty($_POST['login']) || empty($_POST['password']))return false;
			$login=$_POST['login'];
			if(empty(self::$users[$login]))return false;
			if(self::$users[$login]!=$_POST['password'])return false;
			$password=md5(self::$users[$login]);
		}else{
			if(empty($_COOKIE['wrc_user']))return false;
			if(empty($_COOKIE['wrc_password']))return false;
			$login=$_COOKIE['wrc_user'];
			if(empty(self::$users[$login]))return false;
			$password=md5(self::$users[$login]);
			if($password!=$_COOKIE['wrc_password'])return false;
		}
		self::$user=$login;
		SetCookie("wrc_user",$login,time()+60*60*24*7);  
		SetCookie("wrc_password",$password,time()+60*60*24*7);  
		return true;
	}


	static public function Save(){
		if(empty($_POST['save']))return false;
		$file=self::getFilePath(self::LGet('edit'));
		file_put_contents($file,self::LPost('file-content'));

	}

	static public function CreateFolder(){
		if(empty($_GET['create-folder']))return false;
		$folder=self::getFilePath(self::LGet('create-folder'));
		if(!is_dir($folder))mkdir($folder);
		self::Redirect('?path='.self::LGet('path'));
	}

	static public function Rename(){
		if(empty($_GET['srcfile']) && empty($_GET['rename']))return false;
		$old_name=self::getFilePath(self::LGet('srcfile'));
		$rename=self::getFilePath(self::LGet('rename'));
		rename($old_name,$rename);
		self::Redirect('?path='.self::LGet('path'));
	}

	static public function Zip(){
		if(empty($_GET['zip']))return false;
		$name=self::LGet('zip');
		self::Archive($name,self::getFilePath());
		self::Redirect('?path='.self::LGet('path'));
	}

	static public function Unzip(){
		if(empty($_GET['unzip']))return false;
		$file=self::getFilePath(self::LGet('unzip'));
		$zip = new ZipArchive;
	    $zip->open($file);
	    $zip->extractTo(self::getFilePath());
	    $zip->close();
		self::Redirect('?path='.self::LGet('path'));
	}

	static public function Delete(){
		if(empty($_GET['delete']))return false;
		$path=self::getFilePath(self::LGet('delete'));
		if(is_file($path)){
			unlink($path);
		}else{
			self::DeleteFolder($path);
		}
		
		self::Redirect('?path='.self::LGet('path'));
	}

	static public function DeleteFolder($path=''){
		if(!file_exists($path))return false;
		$List=scandir($path);
		foreach($List as $file){
			if($file=='.' OR $file=='..' )continue;
			$FilePath=$path. DIRECTORY_SEPARATOR .$file;


			if(is_file($FilePath)){
				unlink($FilePath);
			}else{
				self::DeleteFolder($FilePath);
			}
			
		}
		

		if( __DIR__ !== $path){
			$List=scandir($path);
			if(count($List) < 3){
				rmdir($path);
			}
		}
		
		
	}

	static public function Download(){
		if(empty($_GET['download']))return false;
		$file=self::getFilePath(self::LGet('download'));
		$size=filesize($file);
		if (file_exists($file)) {
		    header('Content-Description: File Transfer');
		    header('Content-Type: application/octet-stream');
		    header('Content-Disposition: attachment; filename=' . basename($file));
		    header('Expires: 0');
		    header('Cache-Control: must-revalidate');
		    header('Pragma: public');
		    header('Content-Length: ' . filesize($file));
		    readfile($file);
		}
		exit;
	}


	static public function Upload(){
		if(empty($_FILES["uploaded_file"]))return false;
		$uploadfile=self::getFilePath(basename($_FILES['uploaded_file']['name']));

		if(move_uploaded_file($_FILES['uploaded_file']['tmp_name'], $uploadfile)){
		    
		}else{
		    
		}
	}

	static public function IsZip($file=''){
		return strpos(strtolower($file),'.zip');
	}

	static public function DirTree($dir){
		if(!file_exists($dir))return false;
		$ListFiles=Array();
		$List=scandir($dir);
		foreach($List as $file){
			if($file=='.' OR $file=='..' )continue;
			$FilePath=$dir.'/'.$file;
			if(is_file($FilePath)){
				array_push($ListFiles,$FilePath);
			}else{
				$New=self::DirTree($FilePath);
				if($New){
					$ListFiles=array_merge($ListFiles,$New);
				}
			}
		}
		if(count($ListFiles)==0)array_push($ListFiles,$dir);
		return $ListFiles;
		
	}

	static public function Archive($name='archive',$path=''){
		if(empty($path))return false;

		$len=strlen($path);
		$path_name=$path.$name;
		$list_archive=Array();
		if(is_dir($path_name)){
			$list_archive=self::DirTree($path_name);
		}else{
			array_push($list_archive,$path_name);
		}

		$zip = new ZipArchive(); 
		$zip->open($path_name.".zip", ZIPARCHIVE::CREATE); 
		foreach($list_archive as $add){
			$zip_dir=substr($add,$len);
			if(is_dir($add)){
				$zip->addEmptyDir($zip_dir); 
			}else{
				$zip->addFile($add,$zip_dir); 
			}
			
			
		}
		$zip->close();
	}


	static public function Content(){
		if(self::$view=='')self::ViewLogin();
		if(self::$view=='fm')self::FileManager();
		if(self::$view=='edit')self::ViewEdit();
	}

	static public function ViewLogin(){
	?>
	<div class="view-login">
		<div class="frmLogin">
			<form method="post">
				<div class="lbl">Login</div>
				<div class="row"><input type="text" name="login" class="input" value=""></div>
				<div class="lbl">Password</div>
				<div class="row"><input type="password" name="password" class="input" value=""></div>
				<div class="row"><input type="submit" class="button" value="Sing in"></div>
				<input type="hidden" name="frm-login" value="yes">
			</form>
		</div>
	</div>
	<?php
	}

	static public function FileManager(){
		
		?>
		<div class="ToolBar">
			<div class="item">
				<div class="button" onclick="CreateFolder();">
					<?php self::IconNewFolder(); ?>
					New Folder
				</div>
			</div>
			<div class="item">
				<div class="button" onclick="WinUploadOpen();"><?php self::IconUpload();?>Upload</div>
			</div>
			
		</div>

		<div class="WinUpload WinCenter">
			<div class="win">
				<div class="title">Upload file</div>
				<div class="close" onclick="WinUploadClose();"><?php echo self::IconClose();?></div>
				<form enctype="multipart/form-data" method="POST">
					<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo self::$max_file_size;?>" />
				    <input name="uploaded_file" type="file" />
				    <div class="row">
				    	<input type="submit" class="butUpload" value="Upload" />
				    </div>
				    
				</form>
			</div>
		</div>
		
		<?php self::BreadCrumbs(); ?>
		<table class="file-manager">
			<thead>
				<tr>
					<td>Name</td>
					<td>Size</td>
					<td>Modified</td>
					<td></td>
				</tr>
			</thead>
			<tbody>
			<?php if(self::$path!=''): ?>
				<tr>
					<td><a href="?path=<?php echo  self::$back?>"><?php echo self::IconBack();?></a></td>
					<td></td>
					<td></td>
					<td></td>
				</tr>
			<?php endif; ?>
			<?php
			echo '';
			$files = scandir(self::$root.'/'.self::$path);
			foreach($files as $file){
				if($file=='.' OR $file=='..') continue;
				$type='file';
				$size='Folder';
				$FilePath=self::getFilePath($file);
				$date=date('Y-m-d H:i:s',filectime($FilePath));
				if(is_dir(self::$root.'/'.self::$path.'/'.$file)){
					$url=self::$path.'/'.$file;
					$url=urlencode($url);
					$url="?path=".$url;
					$type='folder';
				}else{
					$url="?path=".self::$path."&edit=".$file;
					$size=round(filesize($FilePath)/1024,4).' KB';
				}
				?>
				<tr>
					<td><a href="<?php echo $url;?>" class="fm-name type-<?php echo $type;?>"><?php self::getIcon($type);?> <?php echo $file;?></a></td>
					<td><?php echo $size;?></td>
					<td><?php echo $date;?></td>
					<td>
						<div class="fm-menu">
							<?php self::IconListMenu();?>
							<div class="list">
								<div class="item" onclick="Rename('<?php echo $file;?>');" ><?php self::IconRename();?> Rename</div>
								<?php if(self::IsZip($file)): ?>
								<div class="item" onclick="Unzip('<?php echo $file;?>');" ><?php self::IconUnZip();?> UnZip</div>
								<?php else: ?>
								<div class="item" onclick="Zip('<?php echo $file;?>');" ><?php self::IconZip();?> Zip</div>
								<?php endif; ?>
								<?php if($type!='folder'): ?>
								<a href="?path=<?php echo self::$path."&download=".$file;?>" class="item"><?php self::IconDownload();?> Download</a>
								<?php endif; ?>
								<div class="item icoDelete" onclick="Delete('<?php echo $file;?>');" ><?php self::IconDelete();?> Delete</div>
							</div>
						</div>
					</td>
				</tr>
				<?php
				
			}
			?>
			</tbody>
		</table>
		<?php
	}



	

	static public function ViewEdit(){
		$file_name=self::LGet('edit');
		$path = self::getFilePath($file_name);

		if(!file_exists($path)){
			self::NoteError('File not found: '.$path);
			return false;
		}

		$content = file_get_contents($path);
		$content = htmlspecialchars($content);
		self::BreadCrumbs();
		?>
		<div class="file-name"><b>File:</b> <?php echo $file_name;?></div>
		<div class="editor">
			<form method="post">
				<div class="ToolBar">
					<input type="submit" class="butOk" value="Save">
					<a href="?path=<?php echo self::LGet('path');?>" class="butCnacel" >Cnacel</a>
				</div>
				<textarea name="file-content"><?php echo $content;?></textarea>
				<input type="hidden" name="save" value="yes">
			</form>
		</div>
		
		<?php
	}


	

	static public function Template(){
	?>
	<!DOCTYPE html>
	<html>
	<head>
		<meta charset="utf-8" />
		<title>Web Raccoon <?php echo self::$varsion;?></title>
		<?php
		self::Style();
		self::Script();
		?>
	</head>
	<body>
		<header>
			File Manager <b>Web Raccoon</b> <label>v<?php echo self::$varsion;?></label>
			<?php if(self::$user): ?>
			<div class="pos-right">
				<div class="account">
					<?php self::IconUser(); ?>
					<?php echo self::$user;?>
					<span class="arrow"><?php self::IconArrowDown(); ?></span>
					<div class="account-menu">
						<div class="item">
							<a href="?logout=y">Sing Out</a>
						</div>
					</div>
				</div>
				
				
			</div>
			<?php endif; ?>
		</header>
		<div class="main">
			
			<?php self::Content();?>
		</div>
		<script></script>
	</body>
	</html>	
	<?php
	}

	static public function BreadCrumbs(){
	?><div class="BreadCrumbs"><?php self::IconHome();?> <?php echo self::$path?></div><?php
	}

	static public function Style(){
	?>
	<style>

		:root {
		  --select-color: #e6e9e9;
		}
		body{
			font-family:  sans-serif;
			font-size: 14px;
			color: black;
			padding: 0px;
			margin: 0px;
		}

		a{
			color: black;
			text-decoration: none;
		}

		svg{
			width: 14px;
			fill: #18b6d5;
		}

		.WinCenter{
			position: fixed;
			top: 0px;
			left: 0px;
			width: 100%;
			height: 100%;
			display: flex;
			align-items: center;
			justify-content: center;
			background: rgba(255,255,255,0.6);
			z-index: 600px;
		}

		header{
			text-align: center;
			font-size: 18px;
			background: linear-gradient(to top, #b7b7b7, #e9e9e9);
			box-shadow: 5px 5px 5px rgba(0,0,0,0.5);
			padding: 6px 0px;
			position: relative;
		}

		header label{
			font-size: 14px;
		}

		header .pos-right{
			position: absolute;
			top: 5px;
			right: 8px;
		}

		header .pos-right .account svg{
			width: 12px;
			fill: #565656;
			margin-right: 4px;
		}

		header .pos-right .account{
			font-size: 14px;
			display: flex;
			align-items: center;
			justify-content: left;
			cursor: pointer;
			position: relative;
		}

		header .pos-right .account .arrow svg{
			width: 8px;
			margin-left: 2px;
		}

		header .pos-right .account .account-menu{
			display: none;
		}

		header .pos-right .account:hover .account-menu{
			display: block;
			position: absolute;
			top: 14px;
			right: 0px;
			width: 80px;
			background: #e9e9e9;
			font-size: 12px;
			padding: 4px 2px;
			border-radius: 5px;
			border: solid 1px #ccc;
			box-shadow: 5px 5px 5px rgba(0,0,0,0.2); 
		}

		header .pos-right .account .account-menu .item{
			display: flex;
			justify-content: right;
		}

		.main{
			padding: 15px;
		}

		.BreadCrumbs{
			margin: 10px 0px 5px 0px;
		}

		.WinUpload{
			display: none;
		}

		.WinUpload.open{
			display: flex;
		}

		.WinUpload .win{
			background: #fff;
			width: 320px;
			min-height: 80px;
			padding: 10px;
			border-radius: 5px;
			border: solid 1px #ccc;
			box-shadow: 5px 5px 5px rgba(0,0,0,0.2); 
			position: relative;
		}

		.WinUpload .win .title{
			font-size: 18px;
			font-weight: bold;
			text-align: center;
		}

		.WinUpload .win .close{
			position: absolute;
			top: 10px;
			right: 10px;
			cursor: pointer;
		}

		

		.WinUpload .win .close svg{
			fill: black;
		}

		.WinUpload .win .close svg:hover{
			fill: red;
		}

		.WinUpload .win form{
			margin-top: 15px;
		}

		.WinUpload .win .row{
			margin-top: 10px;
		}

		.WinUpload .win .butUpload{
			background: linear-gradient(to top, #00b7ff, #8ad7f5);
			width: 100%;
			padding: 10px 0px;
			font-size: 16px;
			font-weight: bold;
			color: white;
			border: none;
			margin-top: 10px;
			cursor: pointer;
		}

		.main table.file-manager{
			width: 100%;
			border-collapse: collapse;
		}

		.main table thead{
			font-weight: bold;
		}

		.main table thead td{
			background: linear-gradient(to top, #cbcbcb, #f3f3f3);
		}

		.main table td{
			padding: 5px 5px;
		}

		.main table.file-manager tbody tr:hover td{
			background-color: var(--select-color);
		}

		.main table.file-manager .fm-name{
			display: flex;
			justify-content: left;
			align-items: center;
			flex-wrap: wrap;
		}

		.main table.file-manager .fm-name.type-file svg{
			width: 12px;
		}

		.main table.file-manager .fm-name svg{
			margin-right: 5px;
		}

		.file-manager  .fm-menu{
			position: relative;
			cursor: pointer;
		}

		.file-manager  .fm-menu .list{
			display: none;
			position: absolute;
			right: 40px;
			top: 16px;
			min-width: 140px;
			background: #fff;
			z-index: 500;
			border: solid 1px #ccc;
			border-radius: 4px;
			box-shadow: 5px 5px 5px rgba(0,0,0,0.2); 
		}

		.file-manager  .fm-menu.active .list{
			display: block;
		}

		.file-manager  .fm-menu .list .item{
			padding: 5px;
			display: flex;
			align-items: center;
			justify-content: left;
			border-bottom: solid 1px #ccc;
		}

		.file-manager  .fm-menu .list .item:hover{
			background: var(--select-color);
		}

		.file-manager  .fm-menu .list .item svg{
			margin-right: 5px;
		}

		.file-manager  .fm-menu .list .item.icoDelete{
			color: red;
			
		}
		.file-manager  .fm-menu .list .item.icoDelete svg{
			fill: red;
		}

		.view-login{
			display: flex;
			align-items: center;
			justify-content: center;
		}

		.view-login .frmLogin{
			margin: 20px 0px;
			padding: 10px;
			border: solid #aaa 1px;
			border-radius: 10px;
			box-shadow: 5px 5px 5px rgba(0,0,0,0.2); 
		}
		.view-login .frmLogin .row{
			margin: 4px 0px;
		}
		.view-login .frmLogin .input{
			padding: 8px 4px;
			border-radius: 5px;
			border: solid #aaa 1px;
			width: 240px;
		}

		.view-login .frmLogin .button{
			background: linear-gradient(to top, #00b7ff, #8ad7f5);
			width: 250px;
			padding: 10px 0px;
			font-size: 18px;
			font-weight: bold;
			color: white;
			border: none;
			margin-top: 10px;
		}

		textarea{
			width: 100%;
			height: calc(100vh - 200px);
		}

		.ToolBar{
			display: flex;
			justify-content: left;
			align-items: center;
			flex-wrap: wrap;
			margin: 5px 0px;
		}

		.ToolBar>*{
			margin-right: 5px;
		}

		.ToolBar .button{
			background: linear-gradient(to top, #cbcbcb, #f3f3f3);
		    padding: 5px 15px 5px 8px;
		    font-size: 12px;
		    color: black;
		    border: solid 1px #cbcaca;
		    border-radius: 3px;
		    cursor: pointer;
		    display: flex;
			justify-content: left;
			align-items: center;
		}

		.ToolBar .button:hover{
			background: linear-gradient(to top, #a9a5a5, #f3f3f3);
		}

		.ToolBar .button svg{
			margin-right: 5px;
		}

		

		.NoteError{
			margin: 10px 0px;
			background: red;
			color: white;
			padding: 10px;
			border-radius: 10px;
			font-weight: bold;
		}

		.left{
			display: flex;
			justify-content: left;
			align-items: center;
			flex-wrap: wrap;
		}

		.butOk{
			background: linear-gradient(to top, #188d03, #25df04);
			padding: 5px 15px;
			font-size: 18px;
			color: white;
			border: solid 1px #d9d9d9;
			border-radius: 3px;
			cursor: pointer;
			min-width: 100px;
		}

		.butCnacel{
			background: linear-gradient(to top, #afafaf, #e7e7e7);
			padding: 5px 15px;
			font-size: 18px;
			color: black;
			border: solid 1px #d9d9d9;
			border-radius: 3px;
			cursor: pointer;

		}
		
	</style>
	<?php
	}

	static public function Script(){
		$data=Array(
			'path'=>self::$path,
		);
	?>
	<script>
		const data=<?php echo json_encode($data);?>;

		window.addEventListener('load', ()=>{
			document.querySelectorAll('.file-manager  .fm-menu').forEach((i)=>{
				i.addEventListener('click',function(){
					if(this.classList.contains("active")){
						this.classList.remove('active');
					}else{
						document.querySelectorAll('.file-manager  .fm-menu').forEach((i)=>{
							i.classList.remove('active');
						});
						this.classList.add('active');
					}
				});
			});

			document.addEventListener('click',function(event){
				if ( !event.target.closest('.file-manager  .fm-menu')) {
					document.querySelectorAll('.file-manager  .fm-menu').forEach((i)=>{
						i.classList.remove('active');
					});
				};
			});
		});

		function CreateFolder(){
			let folder = prompt('Create folder:');

			if(folder){
				location.assign('?path='+data.path+'&create-folder='+folder);
			}
		}

		function WinUploadOpen(){
			document.querySelector('.WinUpload').classList.add('open');
		}

		function WinUploadClose(){
			document.querySelector('.WinUpload').classList.remove('open');
		}

		function Rename(name=''){
			let rename = prompt('Rename:',name);

			if(rename){
				location.assign('?path='+data.path+'&srcfile='+name+'&rename='+rename);
			}
		}

		function Zip(name=''){
			if(confirm('Archive in ZIP: '+name+'?')){
				location.assign('?path='+data.path+'&zip='+name);
			}
		}

		function Unzip(name=''){
			if(confirm('Unzip the archive: '+name+'?')){
				location.assign('?path='+data.path+'&unzip='+name);
			}
		}
		
		function Delete(file=''){
			if(confirm('Delete: '+file+'?')){
				location.assign('?path='+data.path+'&delete='+file);
			}
		}
	</script>
	<?php
	}

	static public function NoteError($text=''){
		?><div class="NoteError"><?php echo $text;?></div><?php
	}

	static public function getIcon($type='file'){
		if($type=='folder')self::IconFolder();
		if($type=='file')self::IconFile();
	}

	static public function IconNewFolder(){
		?>
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M512 416c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V96C0 60.7 28.7 32 64 32H192c20.1 0 39.1 9.5 51.2 25.6l19.2 25.6c6 8.1 15.5 12.8 25.6 12.8H448c35.3 0 64 28.7 64 64V416zM232 376c0 13.3 10.7 24 24 24s24-10.7 24-24V312h64c13.3 0 24-10.7 24-24s-10.7-24-24-24H280V200c0-13.3-10.7-24-24-24s-24 10.7-24 24v64H168c-13.3 0-24 10.7-24 24s10.7 24 24 24h64v64z"/></svg>
		<?php
	}

	static public function IconFolder(){
		?>
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M0 96C0 60.7 28.7 32 64 32H196.1c19.1 0 37.4 7.6 50.9 21.1L289.9 96H448c35.3 0 64 28.7 64 64V416c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V96zM64 80c-8.8 0-16 7.2-16 16V416c0 8.8 7.2 16 16 16H448c8.8 0 16-7.2 16-16V160c0-8.8-7.2-16-16-16H286.6c-10.6 0-20.8-4.2-28.3-11.7L213.1 87c-4.5-4.5-10.6-7-17-7H64z"/></svg>
		<?php
	}

	static public function IconFile(){
		?>
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M0 64C0 28.7 28.7 0 64 0H224V128c0 17.7 14.3 32 32 32H384V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V64zm384 64H256V0L384 128z"/></svg>
		<?php
	}

	static public function IconBack(){
		?>
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M512 256A256 256 0 1 0 0 256a256 256 0 1 0 512 0zM231 127c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9l-71 71L376 232c13.3 0 24 10.7 24 24s-10.7 24-24 24l-182.1 0 71 71c9.4 9.4 9.4 24.6 0 33.9s-24.6 9.4-33.9 0L119 273c-9.4-9.4-9.4-24.6 0-33.9L231 127z"/></svg>
		<?php
	}

	static public function IconDelete(){
		?>
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"/></svg>
		<?php
	}

	static public function IconHome(){
		?>
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M575.8 255.5c0 18-15 32.1-32 32.1h-32l.7 160.2c0 2.7-.2 5.4-.5 8.1V472c0 22.1-17.9 40-40 40H456c-1.1 0-2.2 0-3.3-.1c-1.4 .1-2.8 .1-4.2 .1H416 392c-22.1 0-40-17.9-40-40V448 384c0-17.7-14.3-32-32-32H256c-17.7 0-32 14.3-32 32v64 24c0 22.1-17.9 40-40 40H160 128.1c-1.5 0-3-.1-4.5-.2c-1.2 .1-2.4 .2-3.6 .2H104c-22.1 0-40-17.9-40-40V360c0-.9 0-1.9 .1-2.8V287.6H32c-18 0-32-14-32-32.1c0-9 3-17 10-24L266.4 8c7-7 15-8 22-8s15 2 21 7L564.8 231.5c8 7 12 15 11 24z"/></svg>
		<?php
	}

	static public function IconLink(){
		?>
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M320 0c-17.7 0-32 14.3-32 32s14.3 32 32 32h82.7L201.4 265.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L448 109.3V192c0 17.7 14.3 32 32 32s32-14.3 32-32V32c0-17.7-14.3-32-32-32H320zM80 32C35.8 32 0 67.8 0 112V432c0 44.2 35.8 80 80 80H400c44.2 0 80-35.8 80-80V320c0-17.7-14.3-32-32-32s-32 14.3-32 32V432c0 8.8-7.2 16-16 16H80c-8.8 0-16-7.2-16-16V112c0-8.8 7.2-16 16-16H192c17.7 0 32-14.3 32-32s-14.3-32-32-32H80z"/></svg>
		<?php
	}

	static public function IconRename(){
		?>
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M441 58.9L453.1 71c9.4 9.4 9.4 24.6 0 33.9L424 134.1 377.9 88 407 58.9c9.4-9.4 24.6-9.4 33.9 0zM209.8 256.2L344 121.9 390.1 168 255.8 302.2c-2.9 2.9-6.5 5-10.4 6.1l-58.5 16.7 16.7-58.5c1.1-3.9 3.2-7.5 6.1-10.4zM373.1 25L175.8 222.2c-8.7 8.7-15 19.4-18.3 31.1l-28.6 100c-2.4 8.4-.1 17.4 6.1 23.6s15.2 8.5 23.6 6.1l100-28.6c11.8-3.4 22.5-9.7 31.1-18.3L487 138.9c28.1-28.1 28.1-73.7 0-101.8L474.9 25C446.8-3.1 401.2-3.1 373.1 25zM88 64C39.4 64 0 103.4 0 152V424c0 48.6 39.4 88 88 88H360c48.6 0 88-39.4 88-88V312c0-13.3-10.7-24-24-24s-24 10.7-24 24V424c0 22.1-17.9 40-40 40H88c-22.1 0-40-17.9-40-40V152c0-22.1 17.9-40 40-40H200c13.3 0 24-10.7 24-24s-10.7-24-24-24H88z"/></svg>
		<?php
	}

	static public function IconDownload(){
		?>
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M288 32c0-17.7-14.3-32-32-32s-32 14.3-32 32V274.7l-73.4-73.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l128 128c12.5 12.5 32.8 12.5 45.3 0l128-128c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L288 274.7V32zM64 352c-35.3 0-64 28.7-64 64v32c0 35.3 28.7 64 64 64H448c35.3 0 64-28.7 64-64V416c0-35.3-28.7-64-64-64H346.5l-45.3 45.3c-25 25-65.5 25-90.5 0L165.5 352H64zm368 56a24 24 0 1 1 0 48 24 24 0 1 1 0-48z"/></svg>
		<?php
	}

	static public function IconUpload(){
		?>
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path d="M144 480C64.5 480 0 415.5 0 336c0-62.8 40.2-116.2 96.2-135.9c-.1-2.7-.2-5.4-.2-8.1c0-88.4 71.6-160 160-160c59.3 0 111 32.2 138.7 80.2C409.9 102 428.3 96 448 96c53 0 96 43 96 96c0 12.2-2.3 23.8-6.4 34.6C596 238.4 640 290.1 640 352c0 70.7-57.3 128-128 128H144zm79-217c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0l39-39V392c0 13.3 10.7 24 24 24s24-10.7 24-24V257.9l39 39c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-80-80c-9.4-9.4-24.6-9.4-33.9 0l-80 80z"/></svg>
		<?php
	}

	static public function IconZip(){
		?>
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M64 0C28.7 0 0 28.7 0 64V448c0 35.3 28.7 64 64 64H320c35.3 0 64-28.7 64-64V160H256c-17.7 0-32-14.3-32-32V0H64zM256 0V128H384L256 0zM96 48c0-8.8 7.2-16 16-16h32c8.8 0 16 7.2 16 16s-7.2 16-16 16H112c-8.8 0-16-7.2-16-16zm0 64c0-8.8 7.2-16 16-16h32c8.8 0 16 7.2 16 16s-7.2 16-16 16H112c-8.8 0-16-7.2-16-16zm0 64c0-8.8 7.2-16 16-16h32c8.8 0 16 7.2 16 16s-7.2 16-16 16H112c-8.8 0-16-7.2-16-16zm-6.3 71.8c3.7-14 16.4-23.8 30.9-23.8h14.8c14.5 0 27.2 9.7 30.9 23.8l23.5 88.2c1.4 5.4 2.1 10.9 2.1 16.4c0 35.2-28.8 63.7-64 63.7s-64-28.5-64-63.7c0-5.5 .7-11.1 2.1-16.4l23.5-88.2zM112 336c-8.8 0-16 7.2-16 16s7.2 16 16 16h32c8.8 0 16-7.2 16-16s-7.2-16-16-16H112z"/></svg>
		<?php
	}

	static public function IconUnZip(){
		?>
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path d="M58.9 42.1c3-6.1 9.6-9.6 16.3-8.7L320 64 564.8 33.4c6.7-.8 13.3 2.7 16.3 8.7l41.7 83.4c9 17.9-.6 39.6-19.8 45.1L439.6 217.3c-13.9 4-28.8-1.9-36.2-14.3L320 64 236.6 203c-7.4 12.4-22.3 18.3-36.2 14.3L37.1 170.6c-19.3-5.5-28.8-27.2-19.8-45.1L58.9 42.1zM321.1 128l54.9 91.4c14.9 24.8 44.6 36.6 72.5 28.6L576 211.6v167c0 22-15 41.2-36.4 46.6l-204.1 51c-10.2 2.6-20.9 2.6-31 0l-204.1-51C79 419.7 64 400.5 64 378.5v-167L191.6 248c27.8 8 57.6-3.8 72.5-28.6L318.9 128h2.2z"/></svg>
		<?php
	}

	static public function IconUser(){
		?>
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-45.7 48C79.8 304 0 383.8 0 482.3C0 498.7 13.3 512 29.7 512H418.3c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304H178.3z"/></svg>
		<?php
	}

	static public function IconArrowDown(){
		?>
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path d="M182.6 470.6c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-9.2-9.2-11.9-22.9-6.9-34.9s16.6-19.8 29.6-19.8H288c12.9 0 24.6 7.8 29.6 19.8s2.2 25.7-6.9 34.9l-128 128z"/></svg>
		<?php
	}

	static public function IconMenu(){
		?>
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M0 96C0 78.3 14.3 64 32 64H416c17.7 0 32 14.3 32 32s-14.3 32-32 32H32C14.3 128 0 113.7 0 96zM0 256c0-17.7 14.3-32 32-32H416c17.7 0 32 14.3 32 32s-14.3 32-32 32H32c-17.7 0-32-14.3-32-32zM448 416c0 17.7-14.3 32-32 32H32c-17.7 0-32-14.3-32-32s14.3-32 32-32H416c17.7 0 32 14.3 32 32z"/></svg>
		<?php
	}

	static public function IconListMenu(){
		?>
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M384 480c35.3 0 64-28.7 64-64l0-320c0-35.3-28.7-64-64-64L64 32C28.7 32 0 60.7 0 96L0 416c0 35.3 28.7 64 64 64l320 0zM224 352c-6.7 0-13-2.8-17.6-7.7l-104-112c-6.5-7-8.2-17.2-4.4-25.9s12.5-14.4 22-14.4l208 0c9.5 0 18.2 5.7 22 14.4s2.1 18.9-4.4 25.9l-104 112c-4.5 4.9-10.9 7.7-17.6 7.7z"/></svg>
		<?php
	}

	static public function IconClose(){
		?>
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg>
		<?php
	}



}



WebRaccoon::Start();



