<?php

define( "FILE_DIR", "image/test/");

session_start();
header('Expires:-1');
header('Cache-Control:');
header('Pragma:');

require_once('config.php');
require ('../wp-load.php');

$type = "contact";
if(function_exists("contact_base_data")) {
	$base = contact_base_data($type);
	$sendemail = $base['from'];
	$email     = $base['to'];
	$subject   = $base['sabject'];
} else {
	exit("プラグインを有効化してください。");
}
query_posts(array( 'pagename' => 'contact' ));
$post = get_post($id = 17); // FIXME: 環境依存

get_header();

?>
		<section id="contact">
			<p class="pan"><?php echo bcn_display(); ?></p>

			<h1>CONTACT US<span>お問い合わせ</span></h1>
			<div class="wrap">
<?php

//各種状態分岐
	// ファイルのアップロード
	if( !empty($_FILES['attachment_file']['tmp_name']) ) {

		$upload_res = move_uploaded_file( $_FILES['attachment_file']['tmp_name'], FILE_DIR.$_FILES['attachment_file']['name']);

		if( $upload_res !== true ) {
			$error[] = 'ファイルのアップロードに失敗しました。';
		} else {
			$clean['attachment_file'] = $_FILES['attachment_file']['name'];
		}
	}
switch( $_POST["mode"] ){
    case "confirm":

		//エラーチェック
		$error = error_check($_POST);

		if($error) {
			foreach($_POST as $key => $value){
				if(!is_array($value)) $data[$key] = htmlspecialchars($value, ENT_QUOTES);
			}
			$contact_method = select_create($array_contact_method, $_POST["contact_method"]);
			$contact_kind  = select_create($array_contact_kind, $_POST["contact_kind"]);
			$place  = select_create($state, $_POST["place"]);
			include 'tmpl/entry.php';
		} else {
			$_SESSION["complete"] = "k59yn0LU";
			//テンプレートファイル成型
			foreach($_POST as $key => $value){
				if(!is_array($value)) {
					if($key != "mode") $hidden .= "<input type='hidden' name='" . $key . "' value='" . htmlspecialchars($value, ENT_QUOTES) . "' />";
					$data[$key] = htmlspecialchars($value, ENT_QUOTES);
				} else {
					$children = "";
					foreach($value as $num => $child) {
						if (end(array_keys($value)) === $num) {
							$children .= $child;
						} else {
							$children .= $child.'、';
						}
					}
					$data[$key] = htmlspecialchars($children, ENT_QUOTES);
					$hidden .= "<input type='hidden' name='" . $key . "' value='" . htmlspecialchars($children, ENT_QUOTES) . "' />";
				}
			}
			include 'tmpl/confirm.php';
		}
    break;

    case "complete":
        if($_POST["back"] == "back"){
			foreach($_POST as $key => $value){
				if(!is_array($value)) $data[$key] = htmlspecialchars($value, ENT_QUOTES);
			}
			$contact_method = select_create($array_contact_method, $_POST["contact_method"]);
			$contact_kind  = select_create($array_contact_kind, $_POST["contact_kind"]);
			$place  = select_create($state, $_POST["place"]);
			include 'tmpl/entry.php';
		} else {
			if(isset($_SESSION["complete"]) && $_SESSION["complete"] == "k59yn0LU") {
				check_admin_referer('contact_log_0613');
				if(function_exists("contact_log_insert")) contact_log_insert($_POST, $type);
				mail_to($emaillist[$_POST["contact_kind"]], $_POST["email"], $subject, $_POST);
    	    	mail_to($emaillist[$_POST["contact_kind"]], $emaillist[$_POST["contact_kind"]], $subject, $_POST);
				unset($_SESSION["complete"]);
			}
			include 'tmpl/complete.php';
			unset($_PSOT);
		}
    break;

    default:
		$contact_method = select_create($array_contact_method);
		$contact_kind  = select_create($array_contact_kind);
		$place  = select_create($state, $_POST["place"]);
        include 'tmpl/entry.php';
    break;
}
?>
			</div>
		</section>

<?php get_footer(); ?>

<?php
//セレクト生成＆チェック関数
function select_create($data, $taget = "") {
	$selected = "";
	$select = "";
	foreach($data as $val){
		if($val == $taget) $selected = ' selected = "selected"';
		$select .= '<option value="'. $val .'"'. $selected .'>'. $val .'</option>';
		$selected = "";
	}
	return $select;
}

//チェックボックス生成＆チェック関数
function checkbox_create($data, $name, $taget = array()) {
	$checked = "";
	$check   = "";
	foreach($data as $key => $val){
		if(is_array($taget)) {
			foreach($taget as $flag) {
				if($val == $flag) $checked = ' checked="checked"';
			}
		}
		$check .= '<li><input type="checkbox" name="'.$name.'['.$key.']" id="'.$name.$key.'" value="'.$val.'"'. $checked .'> <label for="'.$name.$key.'">'.$val.'</label></li>';
		$checked = "";
	}
	return $check;
}

//チェックボックス生成＆チェック関数
function radio_create($data, $name, $taget = "") {
	$checked = "";
	$check   = "";
	foreach($data as $key => $val){
		if($taget == $val) $checked = ' checked="checked"';
		$check .= '<li><input type="radio" name="'.$name.'" id="'.$name.$key.'" value="'.$val.'"'. $checked .'> <label for="'.$name.$key.'">'.$val.'</label></li>';
		$checked = "";
	}
	return $check;
}


//エラーチェック関数
function error_check($data) {
	$message = "";
	if(!$data["name"]) {
		$message .= "お名前をご入力ください。<br />";
	}
	if(!$data["corp"]) {
		$message .= "貴社名をご入力ください。<br />";
	}
	/*if(!$data["tantou"]) {
		$message .= "担当者名をご入力ください。<br />";
	}*/
	/*if(!$data["zip"] || !$data["address"]) {
		$message .= "ご住所をご入力ください。<br />";
	}*/
	if(!$data["email"]) {
		$message .= "E-mailアドレスを入力してください。<br />";
	} elseif(!preg_match('/^[a-zA-Z0-9_\.\-]+?@[A-Za-z0-9_\.\-]+$/',$data["email"])) {
		$message .= "正しいE-mailアドレスをご入力ください。<br />";
	} elseif($data["email"] != $data["cemail"]) {
		$message .= "E-mailアドレスが確認用メールアドレスと一致しません。<br />";
	}
	if(!$data["tel"]) {
		$message .= "電話番号をご入力ください。<br />";
	}
	if(!$data["contact_kind"] || !$data["comment"]) {
		$message .= "お問い合わせ内容をご入力ください。<br />";
	}
	if(!$data["privacycheck"]) {
		$message .= "プライバシーポリシーにチェックを入れてください。<br />";
	}
	return $message;
}

//メール送信関数
function mail_to($mail, $mailto, $subject, $post) {
	global $sendemail;
   	$tmpl = file_get_contents('tmpl/mail.php');

    foreach($post as $key => $value){
		$label = "{" . $key . "}";
		$tmpl  = str_replace($label, htmlspecialchars($value, ENT_QUOTES), $tmpl);
    }
	$tmpl = preg_replace("/{(.+?)}/", "", $tmpl);

    $gmt  = date("Z");
    $abs  = abs($gmt);
    $hour = floor( $abs / 3600 );
    $min  = floor(( $abs - $hour * 3600 ) / 60 );
    $flag = ($gmt >= 0 ) ? "+" : "-";
    $date = date("D, d M Y H:i:s "). sprintf( $flag ."%02d%02d", $hour, $min );

    $header  = "Date: ". $date ."\n";
    $header .= "From: ". $sendemail ."\n";
    $header .= "MIME-Version: 1.0\n";
    $header .= "X-Mailer: PHP/". phpversion() ."\n";
    $header .= "Content-type: text/plain; charset=ISO-2022-JP\n";
    $header .= "Content-Transfer-Encoding: 7bit";

    if($post['title']) {
		$subject = mb_convert_encoding( $post['title'].$subject, 'JIS', 'UTF-8' );
	} else {
		$subject = mb_convert_encoding( $subject, 'JIS', 'UTF-8' );
	}
    $subject = base64_encode($subject);
    $subject = "=?iso-2022-jp?B?". $subject ."?=";

    $body    = htmlspecialchars( $body, ENT_QUOTES );
    $body    = mb_convert_encoding( $tmpl, 'JIS', 'UTF-8' );

    mail( $mailto, $subject, $body, $header );
}

?>