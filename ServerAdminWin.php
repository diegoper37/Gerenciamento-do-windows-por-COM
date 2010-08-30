<?php
/**
*	Classe para gerenciamento do windows.
*	Created on 29/07/2008
*	@author Diego B. Pimentel (diegoper37@gmail.com)
*	@version 1.0
*	@package Windows
*/
class  ServerAdminWin
{

	/**
	 * Nome do computador.
	 * @access public
	 */	
	public $computer = '';	
	
	/**
	 * Objeto COM do windows.
	 * @access public
	 */	
	public $win = '';	
			
	// __construct() {{{

	/** 
	* Construtor que seleciona o nome do servidor onde sera adinistrado o WMS
	*
	* @access public
	*/	
	public function __construct() 
	{
		$this -> win = new COM('winmgmts:\\\\.\root\cimv2');
		$network = new COM('WScript.Network');
		$this -> computer = $network -> ComputerName;		
	}


	// alterPass() {{{
	/** 
	* Altera senha do usuario
	* 
	* Example:
	* <code>
	* require_once 'class.ServerAdminWin.php'
	* $AdminWin = new ServerAdminWin();
	* $retorno = $AdminWin -> alterPass(array(
	* 	'name' => 'diegotv',
	* 	'senha' => '102030'
	* ));
	*
	* echo ($retorno[0])? $retorno[1] : 'Erro: '.$retorno[1];	
	*
	* </code>
	*
	* @param ('name' => '' , 'pass' => '' )
	*
	* @return array
	* @access public
	*/	
	public function alterPass( array $params=array() )
	{
		if(strtolower($params['name']) == 'administrador' || strtolower($params['name']) == 'administrator')
		{
			return array(false , 'Nao permitido alterar usuario administrador!');
		}
		try
		{
			$usuario = $params['name'];
			$senha = $params['pass'];
			$users = $this -> win -> ExecQuery('SELECT * FROM Win32_UserAccount');	
			foreach($users as $user)
			{
				if($user -> Name == $usuario)
				{		
	
						$usr = new COM("WinNT://".$this->computer."/".$usuario.",user") ;
						$usr -> SetPassword ($senha);
						$usr -> SetInfo();
						return array( true , 'Usuario alterado com sucesso');				
				}
			}
			return array(false , 'Usuario não encontrado');
		}catch(com_exception $e) 
		{
			return array(false ,  $e->getMessage());
		}			
	}
	// }}}	
	
	// addUser() {{{
	/** 
	* Adiciona usuario no servidor
	* 
	* Example:
	* <code>
	* require_once 'class.ServerAdminWin.php'
	* $AdminWin = new ServerAdminWin();
	* $retorno = $AdminWin -> addUser(array(
	* 	'name' => 'diegotv',
	* 	'senha' => '102030'
	* ));
	*
	* echo ($retorno[0])? $retorno[1] : 'Erro: '.$retorno[1];
	*
	* </code>
	*
	* @param array ('name' => '' , 'pass' => '' )
	*
	* @return array
	* @access public
	*/	
	public function addUser( array $params=array() )
	{
		if(strtolower($params['name']) == 'administrador' || strtolower($params['name']) == 'administrator')
		{
			return array(false , 'Nao permitido adicionar usuario administrador!');
		}
		
		if(!preg_match('/^([a-zA-Z0-9.\-_+$@])*$/' ,$params['name']))
		{
			return array( false , 'Nome com caracteres invalidos!');
		}

		if(!preg_match('/^([a-zA-Z0-9])*$/' ,$params['senha']))
		{
			return array( false , 'Senha com caracteres invalidos!');
		}
	
		$usuario = substr(preg_replace('/([^a-zA-Z0-9])/','',$params['name']), 0, 20);
		try
		{
			$usuario = $params['name'];
			$senha = $params['pass'];	
			$users = $this -> win -> ExecQuery('SELECT * FROM Win32_UserAccount');			
			foreach($users as $user)
			{
				if($user -> Name == $usuario)
				{
					return array( false , 'Usuario já cadastrado, solicite outro nome para o mesmo');
				}
			}
			$cont = new COM('WinNT://'.$this -> computer.',computer');
			$oUser = $cont -> Create('user', $usuario);
			$oUser -> Put( "Fullname", $usuario.' Streaming' );
			$oUser -> Put( "Description", $usuario.' Streaming' );			
			$oUser -> SetPassword ($senha);			
			$oUser -> SetInfo();	
			$win = new COM('WinNT://'.$this -> computer.'/'.$usuario.',User');
			if($win -> Get('UserFlags') != 66113)
			{
				$win -> Put('UserFlags' , 66113);
				$win -> SetInfo();
			}

			return array( true , 'Usuario '.$usuario.' criado com sucesso e setado para nao expirar nunca <br/>');
		}

		catch (com_exception $e)
		{
			return array( false , $e->getMessage() );
		}
	}
	
	// addUserGroup() {{{
	/** 
	* Adiciona usuario no grupo especificado
	* 
	* Example:
	* <code>
	* require_once 'class.ServerAdminWin.php'
	* $AdminWin = new ServerAdminWin();
	* $retorno = $AdminWin -> addUserGroup(array(
	* 	'name' => 'diegotv',
	* 	'group' => 'streaming'
	* ));
	*
	* echo ($retorno[0])? $retorno[1] : 'Erro: '.$retorno[1];
	*
	* </code>
	*
	* @param array ('name' => '' , 'group' => '' )
	*
	* @return array
	* @access public
	*/		
	public function addUserGroup( array $params=array() )
	{
		try
		{
			$Group = new COM('WinNT://'.$this -> computer.'/'.$params['group']);
			$Group -> Add('WinNT://'.$this -> computer.'/'.$params['name']);
			return array( true , 'Usuario adicionado ao grupo');
		}catch(com_exception $e)
		{
			return array( false , $e->getMessage() );
		}
	}
	// }}}
	
	// remUser() {{{
	/** 
	* Remove usuario do windows
	* 
	* Example:
	* <code>
	* require_once 'class.ServerAdminWin.php'
	* $Admin = new ServerAdminWin();
	* $retorno = $Admin -> remUser('diegotv');
	* echo ($retorno[0])? $retorno[1] : 'Erro: '.$retorno[1];
	* </code>
	*
	* @param $usuario nome do usuario a ser excluido
	*
	* @return array
	* @access public
	*/
	public function remUser( $usuario = '')
	{
		if(strtolower($usuario) == 'administrador' || strtolower($usuario) == 'administrator')
		{
			return array(false , 'Nao permitido excluir usuario administrador!');
		}
		try
		{
			$users = $this -> win -> ExecQuery('SELECT * FROM Win32_UserAccount');	
			foreach($users as $user)
			{
				if($user -> Name == $usuario)
				{
					$winnt = new COM('WinNT://'.$this -> computer.',computer');
					$oUser = $winnt -> Delete('user', $usuario);
					return array( true , 'Usuario excluido com sucesso');
				}
			}
			return array(false , 'Usuario não encontrado');
		}catch(com_exception $e)
		{
			return array( false , $e->getMessage() );
		}		
	}
	// }}}
	
	// createFolder() {{{
	/** 
	* Cria Pasta no Windows
	* 
	* Example:
	* <code>
	* require_once 'class.ServerAdminWin.php'
	* $Admin = new ServerAdminWin();
	* $retorno = $Admin -> createFolder('C:\novaPasta');
	* echo ($retorno[0])? $retorno[1] : 'Erro: '.$retorno[1];
	* </code>
	*
	* @param $ExplicitPath
	*
	* @return array
	* @access public
	*/
	public function createFolder($ExplicitPath = '')
	{
		try
		{
			$folder = new COM("Scripting.FileSystemObject");
			$objFolder = $folder -> CreateFolder($ExplicitPath);
			return array(true , 'Pasta criada no path: '.$ExplicitPath);
		}catch(com_exception $e)
		{
			return array( false , $e->getMessage());
		}
	}
	// }}}
		
	// remFolder() {{{
	/** 
	* Remove Pasta no Windows
	* 
	* Example:
	* <code>
	* require_once 'class.ServerAdminWin.php'
	* $Admin = new ServerAdminWin();
	* $retorno = $Admin -> remFolder('C:\novaPasta');
	* echo ($retorno[0])? $retorno[1] : 'Erro: '.$retorno[1];
	* </code>
	*
	* @param $ExplicitPath
	*
	* @return array
	* @access public
	*/
	public function remFolder($ExplicitPath = '')
	{
		try
		{
			$folder = new COM("Scripting.FileSystemObject");
			$objFolder = $folder -> DeleteFolder($ExplicitPath);
			return array(true , 'Pasta removida no path: '.$ExplicitPath);
		}catch(com_exception $e)
		{
			return array( false ,$e->getMessage());
		}
	}
	// }}}
	
	// accessRootFolder() {{{
	/** 
	* Seta permissao total de usuario na pasta inforamada
	* 
	* Example:
	* <code>
	* require_once 'class.ServerAdminWin.php'
	* $Admin = new ServerAdminWin();
	* $retorno = $Admin -> accessRootFolder(array(
	* 	'name' => 'diegotv',
	* 	'path' => 'C:\novaPasta'
	* ));
	* echo ($retorno[0])? $retorno[1] : 'Erro: '.$retorno[1];
	* </code>
	*
	* @param array ('name' => '' , 'path' => '' )
	*
	* @return array
	* @access public
	*/		
	public function accessRootFolder( array $params=array() )
	{
		if(exec("C:\\WINDOWS\\system32\\cacls.exe ".$params['path']." /T /E /G ".$params['name'].":F"))
		{
			return array( true , 'Permissoes setadas com sucesso!');
		}
		return array( false , $e->getMessage());
	}
	// }}}	
}

//$Admin = new ServerAdminWin();

//$retorno = $Admin -> addUser(array('name' => 'superteste2.streaming','pass' => '102030'));
/*
echo ($retorno[0])? $retorno[1] : 'Erro: '.$retorno[1]; echo '<br>';
$retorno = $Admin -> addUserGroup(array('name' => 'novaPasta','group' => 'streaming'));
echo ($retorno[0])? $retorno[1] : 'Erro: '.$retorno[1]; echo '<br>';
$retorno = $Admin -> createFolder('C:\novaPasta');
echo ($retorno[0])? $retorno[1] : 'Erro: '.$retorno[1]; echo '<br>';
$retorno = $Admin -> accessRootFolder( array('name' => 'novaPasta', 'path' => 'C:\novaPasta'));
echo ($retorno[0])? $retorno[1] : 'Erro: '.$retorno[1]; echo '<br>';*/


/*$retorno = $Admin -> remUser('novaPasta');
echo ($retorno[0])? $retorno[1] : 'Erro: '.$retorno[1]; echo '<br>';
$retorno = $Admin -> remFolder('C:\novaPasta');
echo ($retorno[0])? $retorno[1] : 'Erro: '.$retorno[1];*/
?>