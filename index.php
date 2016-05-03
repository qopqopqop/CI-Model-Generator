<?php 
	session_start();
	if (empty($_POST)):
?>
<!DOCTYPE html>
<html>
  <head>
    <title>CI Model Generator</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
  </head>
  <body>
    <div class="container">
      <form action="" method="post" id="form_setup">
        <div class="row">
          <div class="col-md-3">Hostname</div>
          <div class="col-md-3"><input class="form-control" type="text" id="hostname" name="hostname" <?=empty($_SESSION['hostname'])?'':'value="'.$_SESSION['hostname'].'"'?>></div>
        </div>
        <div class="row">
          <div class="col-md-3">Username</div>
          <div class="col-md-3"><input class="form-control" type="text" id="username" name="username" <?=empty($_SESSION['username'])?'':'value="'.$_SESSION['username'].'"'?>></div>
        </div>
        <div class="row">
          <div class="col-md-3">Password</div>
          <div class="col-md-3"><input class="form-control" type="text" id="password" name="password" <?=empty($_SESSION['password'])?'':'value="'.$_SESSION['password'].'"'?>></div>
          <div class="col-md-3"><button id="check_connection" class="btn">cek koneksi</button></div>
        </div>
        <div class="row">
          <div class="col-md-3">Database</div>
          <div class="col-md-3">
            <select class="form-control" id="database_name" name="database_name"></select>
          </div>
        </div>
        <div class="row">
          <div class="col-md-3">Table</div>
          <div class="col-md-3">
            <select class="form-control" id="table_name" name="table_name"></select>
          </div>
        </div>
        <div class="row">
          <div class="col-md-3">Class Name</div>
          <div class="col-md-3"><input class="form-control" type="text" id="class_name" name="class_name"></div>
        </div>
        <div class="row">
          <div class="col-md-3">Tab Length</div>
          <div class="col-md-3"><input class="form-control" type="text" id="tab_length" name="tab_length" value="1"></div>
          <div class="col-md-3"><button id="generate" class="btn">generate</button></div>
        </div>
      </form>
    </div>

    <div class="container">
      <div class="row">
        <div class="col-md-12">
          <textarea id="compilled" class="col-md-12" onclick="this.focus(); this.select()"></textarea>
        </div>
      </div>
    </div>
  </body>
  <script type='text/javascript' src="assets/js/jquery-1.10.2.js"></script>
  <script type="text/javascript">
    $('#check_connection').on('click', function(e){
      e.preventDefault();
      db_list();
    });
    $('#generate').on('click', function(e){
      e.preventDefault();
      generate();
    });

    function generate(){
      $.ajax({
        url: 'index.php?r=generate',
        dataType: 'json',
        data: $('#form_setup').serialize(),
        method: 'post',
        success: function(resp){
          $('#compilled').html(resp.data);
        }
      });
    }

    function table_list(){
      $.ajax({
        url: 'index.php?r=table_list',
        dataType: 'json',
        data: $('#form_setup').serialize(),
        method: 'post',
        success: function(resp){
          var opt_table = '';
          $.each(resp.data, function(i, j){
            opt_table += '<option value="'+j.table_name+'">'+j.table_name+'</option>';
          });

          $('#table_name').html(opt_table);
          $('#class_name').val($('#table_name option:selected').val());

          $('#table_name').on('change', function(){
            $('#class_name').val('M_'+$('#table_name option:selected').val());
          });
        }
      });
    }

    function db_list(){
      $.ajax({
        url: 'index.php?r=db_list',
        dataType: 'json',
        data: $('#form_setup').serialize(),
        method: 'post',
        success: function(resp){
          var opt_db = '';
          $.each(resp.data, function(i, j){
            opt_db += '<option value="'+j.Database+'">'+j.Database+'</option>';
          });
          $('#database_name').html(opt_db);
          table_list();

          $('#database_name').on('change', function(){
            table_list();
          });
        }
      });
    }
  </script>
</html>
<?php
endif;

$r = empty($_GET['r'])?'display':$_GET['r'];

if ($r=='db_list'){
  db_list();
} elseif($r=='table_list'){
  table_list();
} elseif($r=='generate'){
  generate();
}

function generate(){
  $response = array('status'=>false);
  $db = konek();
  $q = "select * from information_schema.columns where table_schema='".$_POST['database_name']."' and table_name='".$_POST['table_name']."' order by ORDINAL_POSITION";
  if ($db){
    if ($hasil = $db->query($q)){
      $str = '';
      $fields = array();

      while($rs = $hasil->fetch_object()){
        $fields[] = $rs;
      }

      template_class_init($str, $_POST['class_name'], $_POST['tab_length']);
      template_var_init($str, $_POST['table_name'], $fields, $_POST['tab_length']);
      template_construct($str, $_POST['tab_length']);
      template_setter_default($str, $fields, $_POST['tab_length']);
      template_find($str, $fields, $_POST['tab_length']);
      template_insert($str, $fields, $_POST['tab_length']);
      template_update($str, $fields, $_POST['tab_length']);
      template_delete($str, $fields, $_POST['tab_length']);
      template_init($str, $_POST['tab_length']);

      template_class_end($str);

      $response['status'] = true;
      $response['data'] = $str;
    }
  }

  header('Content-Type: application/json');
  echo json_encode($response);
}

function template_init(&$str, $space=0){
  $db = konek();
  $q = "SHOW CREATE TABLE `".$_POST['database_name']."`.`".$_POST['table_name']."`";
  if ($hasil = $db->query($q)){
    
    $rs = $hasil->fetch_array();
    $str .= "\n";
    $str .= "\n";
    $str .= template_space($space)."public function init (){";
    $str .= "\n";
    $str .= template_space($space*2).'$q = "'.str_replace(array("\r\n","\r","\n","\\r","\\n","\\r\\n"),"\n".template_space($space*3),$rs['Create Table']).'";'; 
    $str .= "\n";
    $str .= "\n";
    $str .= template_space($space*2).'return $this->db->query($q);';
    $str .= "\n";
    $str .= template_space($space)."}";
  }
}

function template_find(&$str, $field, $space=0){
  $str .= "\n";
  $str .= "\n";
  $str .= template_space($space).'public function find (){';
    foreach ($field as $f) {
      //if ($f->COLUMN_KEY == 'PRI') continue;
      $str .= "\n";
      $str .= template_space($space*2).'if (!empty($this->'.strtolower($f->COLUMN_NAME).')) $this->db->';
      $str .= ($f->DATA_TYPE == 'char' || $f->DATA_TYPE == 'varchar')?'like':'where';
			$str .= '(';
			$str .= ($f->DATA_TYPE == 'datetime')? '\'date(\'.':'';
      $str .= '$this->_t.\'.'.$f->	COLUMN_NAME;
			$str .= ($f->DATA_TYPE == 'datetime')?')':'';
			$str .= '\', ';
			$str .= '$this->'.strtolower($f->COLUMN_NAME).');';
    //$str .= '($this->_t.\''.$f->COLUMN_NAME.'\', $this->'.strtolower($f->COLUMN_NAME).');';
    }
  $str .= "\n";
  $str .= "\n";
  $str .= template_space($space*2).'if (!empty($this->_sel)) $this->db->select($this->_sel);';
  $str .= "\n";
  $str .= "\n";
  $str .= template_space($space*2).'if (!empty($this->_s)) $this->db->order_by($this->_s);';
  $str .= "\n";
  $str .= "\n";
  $str .= template_space($space*2).'if (!empty($this->_p) && !empty($this->_ipp)) $this->db->limit($this->_ipp, ($this->_p - 1) * $this->_ipp);';
  $str .= "\n";
  $str .= "\n";
  $str .= template_space($space*2).'return $this->db->get($this->_t)->result();';
  $str .= "\n";
  $str .= template_space($space).'}';
}

function template_delete (&$str, $field, $space=0){
  $str .= "\n";
  $str .= "\n";
  $str .= template_space($space).'public function delete (){';
  $str .= "\n";
  $str .= template_space($space*2).'if (';
  $i = 0;
  foreach ($field as $f){
    if ($f->COLUMN_KEY != 'PRI') continue;
    if ($i > 0) $str .= ' && ';
    $str .= '!empty($this->'.strtolower($f->COLUMN_NAME).')';
    $i++;
  }
  $str .= ') return $this->db';
  foreach ($field as $f){
    if ($f->COLUMN_KEY != 'PRI') continue;
    $str .= '->';
    $str .= 'where($this->_t.\'.'.$f->COLUMN_NAME.'\', $this->'.strtolower($f->COLUMN_NAME).')';
    $i++;
  }
  $str .= '->delete($this->_t);';
  $str .= "\n";
  $str .= "\n";
  $str .= template_space($space*2).'return false;';
  $str .= "\n";
  $str .= template_space($space).'}';
}

function template_insert (&$str, $field, $space=0){
  $str .= "\n";
  $str .= "\n";
  $str .= template_space($space).'public function insert (){';
  foreach ($field as $f) {
    //if ($f->COLUMN_KEY == 'PRI') continue;
    $str .= "\n";
    $str .= template_space($space*2).'if (isset($this->'.strtolower($f->COLUMN_NAME).')) $data[\''.$f->COLUMN_NAME.'\'] = $this->'.strtolower($f->COLUMN_NAME).';';
  }
  $str .= "\n";
  $str .= "\n";
  $str .= template_space($space*2).'if (!empty($data)';
  $str .= '){';
  $str .= "\n";
  $str .= template_space($space*3).'return $this->db->insert($this->_t, $data);';
  $str .= "\n";
  $str .= template_space($space*2).'}';
  $str .= "\n";
  $str .= "\n";
  $str .= template_space($space*2).'return false;';
  $str .= "\n";
  $str .= template_space($space).'}';
}

function template_update(&$str, $field, $space=0){
  $str .= "\n";
  $str .= "\n";
  $str .= template_space($space).'public function update (){';
  foreach ($field as $f) {
    //if ($f->COLUMN_KEY == 'PRI') continue;
    $str .= "\n";
    $str .= template_space($space*2).'if (isset($this->'.strtolower($f->COLUMN_NAME).')) $data[\''.$f->COLUMN_NAME.'\'] = $this->'.strtolower($f->COLUMN_NAME).';';
  }
  $str .= "\n";
  $str .= "\n";
  $str .= template_space($space*2).'if (!empty($data)';
  foreach ($field as $f){
    if ($f->COLUMN_KEY != 'PRI') continue;
    $str .= ' && ';
    $str .= '!empty($this->'.strtolower($f->COLUMN_NAME).')';
  }
  $str .= '){';
  $str .= "\n";
  $str .= template_space($space*3).'return $this->db';
  foreach ($field as $f){
    if ($f->COLUMN_KEY != 'PRI') continue;
    $str .= "\n";
    $str .= template_space($space*4).'->where($this->_t.\'.'.strtolower($f->COLUMN_NAME).'\', $this->'.strtolower($f->COLUMN_NAME).')';
  }
  $str .= "\n";
  $str .= template_space($space*4).'->update($this->_t, $data);';
  $str .= "\n";
  $str .= template_space($space*2).'}';
  $str .= "\n";
  $str .= "\n";
  $str .= template_space($space*2).'return false;';
  $str .= "\n";
  $str .= template_space($space).'}';
}

function template_setter_default(&$str, $field, $space=0){
  $var_list = array('_s', '_p', '_ipp');
  foreach ($field as $f){
    $var_list[] = strtolower($f->COLUMN_NAME);
  }
  $str .= "\n";
  foreach ($var_list as $v){
    $str .= "\n";
    $str .= template_space($space).'public function set_'.$v.' ($d){';
    $str .= "\n";
    $str .= template_space($space*2).'$this->'.$v.' = $d;';
    $str .= "\n";
    $str .= template_space($space*2).'return $this;';
    $str .= "\n";
    $str .= template_space($space).'}';
  }

  // packed setter, set at once by array
  $str .= "\n";
  $str .= template_space($space).'public function set__packed ($d){';
  foreach ($var_list as $v){
    $str .= "\n";
    $str .= template_space($space*2).'if (isset($d[\''.$v.'\'])) $this->'.$v.' = $d[\''.$v.'\'];';
  }
  $str .= "\n";
  $str .= template_space($space*2).'return $this;';
  $str .= "\n";
  $str .= template_space($space).'}';
}

function template_var_init(&$str, $table, $field, $space=0){
  $str .= "\n";
  $str .= template_space($space).'private $_t = \''.$table.'\', // table name';
  $str .= "\n";
  $str .= template_space($space*2).'$_sel = \'*\', // column select';
  $str .= "\n";
  $str .= template_space($space*2).'$_s, //TODO : default table sort';
  $str .= "\n";
  $str .= template_space($space*2).'$_p = 1, // default page';
  $str .= "\n";
  $str .= template_space($space*2).'$_ipp = 10';
  $str .= (count($field)>0)?',':';';
  $str .= ' // default item per page';
  foreach ($field as $i=>$f){
    $str .= ($i==0)?'':',';
    $str .= "\n";
    $str .= template_space($space*2).'$'.strtolower($f->COLUMN_NAME);
  }
  $str .= (count($field)>0)?';':'';
}

function template_construct(&$str, $space=0){
  $str .= "\n";
  $str .= "\n";
  $str .= template_space($space)."function __construct(){";
  $str .= "\n";
  $str .= template_space($space*2)."parent::__construct();";
  $str .= "\n";
  $str .= template_space($space)."}";
}

function template_class_init(&$str, $class_name, $space=0){
  $str .= "<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');";
  $str .= "\n";
  $str .= "\n";
  $str .= "class ".ucfirst($class_name)." extends CI_Model {";
}

function template_class_end(&$str){
  $str .= "\n";
  $str .= "\n";
  $str .= "}";
  $str .= "\n";
  $str .= "?>";
}

function template_space($space){
  $response = '';
  for($i=0; $i<$space; $i++){
    //$response .= ' ';
		$response .= "\t";
  }
  return $response;
}

function table_list(){
  $response = array('status'=>false);
  $db = konek();
  $q = "select table_name from information_schema.tables where table_schema='".$_POST['database_name']."'";
  if ($db){
    if ($hasil = $db->query($q)){
      while($rs = $hasil->fetch_object()){
        $data[] = $rs;
      }
      $response['status'] = true;
      $response['data'] = $data;
    }
  }

  header('Content-Type: application/json');
  echo json_encode($response);
}

function db_list(){
  $response = array('status'=>false);
  $db = konek();
  $q = "show databases";
  if ($db){
    if ($hasil = $db->query($q)){
      while($rs = $hasil->fetch_object()){
        $data[] = $rs;
      }
      $response['status'] = true;
      $response['data'] = $data;
    }
  }

  header('Content-Type: application/json');
  echo json_encode($response);
}

function konek(){
  $hostname = $_POST['hostname'];
  $username = $_POST['username'];
  $password = $_POST['password'];
	
	$_SESSION['hostname'] = $hostname;
	$_SESSION['username'] = $username;
	$_SESSION['password'] = $password;
  $database = empty($_POST['database'])?'':$_POST['database'];

  if (!empty($database)){
    $db = new mysqli($hostname, $username, $password, $database);
  }else{
    $db = new mysqli($hostname, $username, $password, $database);
  }

  if ($db->connect_errno > 0) return false; //$db->connect_errno;
  return $db;
}
?>
