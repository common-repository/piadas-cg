<?php
define('PIADAS-CG_VERSION', '1.0.1');
/*
Plugin Name: Piadas Click Grátis
Plugin URI: http://www.clickgratis.com.br/piadas/wordpress/
Description: Piadas Click Grátis
Version: 1.0.1
Author: AgênciaLivre
Author URI: http://www.agencialivre.com.br
License: GPL2
*/

/*  Copyright 2010  AGENCIALIVRE  (email : william@agencialivre.com.br)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

add_action('admin_menu', 'piadas_cg_add_pages');

add_action('piadas_cg_add_post_event', 'piadas_cg_add_post');

//add_action('sp_add_post_event', 'piadas_cg_add_post');

register_activation_hook(__FILE__, 'piadas_cg_activation');  //It seems cannot work in Win32
register_deactivation_hook(__FILE__, 'piadas_cg_deactivation');  //It seems cannot work in Win32

function piadas_cg_deactivation() {
    piadas_cg_clear_schedule();
    delete_option('piadas-cg_categories');
    delete_option('piadas_cg_option');
    remove_action('piadas_cg_add_post_event', 'piadas_cg_add_post');
}

function piadas_cg_clear_schedule() {
    wp_clear_scheduled_hook('pidas_cg_add_post_event');
}

function piadas_cg_update_schedule() {
    if (!wp_next_scheduled('piadas_cg_add_post_event')) {
        wp_schedule_event(time(), 'hourly', 'piadas_cg_add_post_event');
    }
}

function piadas_cg_add_post() {

    $option = get_option('piadas_cg_option');
    if(empty($option['interval']) or $option['interval'] == 0) return true;

    //check whether it is time to publish a new post
   // if (empty($option['lasttime']) || $option['lasttime'] + $option['interval'] * 60 * 60 <= time()) {

        $post = piadas_cg_new_post();
        if  (!empty ($post) ) {
            if (wp_insert_post($post)) {  //publish a new post
                $option['lasttime'] = time();  //update time counter
                update_option('piadas_cg_option', $option);
            }
        }

    //}
}


function piadas_cg_add_pages() {
    add_options_page('Piadas ClickGrátis', 'Piadas ClickGrátis', 'administrator', 'piadas_cg', 'piadas_cg_options_page');
}

function piadas_cg_new_post() {

    $categories = get_option("piadas-cg_categories");
    
    $find = array();
    foreach($categories as $key => $val) 
        if($val == true) array_push($find, $key);

    $findurl = urlencode(implode("|", $find));

    $c = file_get_contents('http://www.clickgratis.com.br/piadas/wordpress/piadafeed.php?cat='.$findurl);

    $d = json_decode($c);

    $catid = get_cat_ID("Piadas");
    if($catid == 0) {
        $catid = wp_create_category("Piadas", 0);
    }

    $post = array();
    $post['post_content'] = sprintf("<div id=\"piadadodia\">
<p>%s</p>
<p>Fonte: <a href=\"%s\" rel=\"canonical\">%s</a></p>
</div>", $d->piada, $d->url, $d->titulo);
    $post['post_title'] = $d->titulo;
    $post['post_status'] = 'publish';
    $post['post_category'] = array($catid);

    return $post;

}

function piadas_cg_options_page() {


    if (!$_POST['feedback']=='') {

        $my_email1="webmaster@clickgratis.com.br";
        $plugin_name="piadas-cg";
        $blog_url_feedback=get_bloginfo('url');
        $user_email=$_POST['email'];
        $user_email=stripslashes($user_email);
        $subject=$_POST['subject'];
        $subject=stripslashes($subject);
        $name=$_POST['name'];
        $name=stripslashes($name);
        $response=$_POST['response'];
        $response=stripslashes($response);
        $category=$_POST['category'];
        $category=stripslashes($category);
        if ($response=="Yes") {
            $response="REQUIRED: ";
        }
        $feedback_feedback=$_POST['feedback'];
        $feedback_feedback=stripslashes($feedback_feedback);
        if ($user_email=="") {
            $headers1 = "From: feedback@";
        } else {
            $headers1 = "From: $user_email";
        }
    $emailsubject1=$response.$plugin_name." - ".$category." - ".$subject;
    $emailmessage1="Blog: $blog_url_feedback\n\nUser Name: $name\n\nUser E-Mail: $user_email\n\nMessage: $feedback_feedback";
    mail($my_email1,$emailsubject1,$emailmessage1,$headers1);
?>

<div class="updated"><p><strong><?php _e('Feedback Sent!', 'mt_trans_domain' ); ?></strong></p></div>

<?php
}

    $option = get_option('piadas_cg_option');
    if(empty($option['interval'])) $option['interval'] = 0;
    if( $_SERVER['REQUEST_METHOD'] == 'POST' ) {

        if(is_numeric($_POST['interval']) && $_POST['interval'] != $option['interval']) {

            $option['interval'] = (int)($_POST['interval']);

            if($option['interval'] > 0) {
                wp_schedule_event(time(), 'hourly', 'piadas_cg_add_post_event');
            } else {
                wp_clear_scheduled_hook('piadas_cg_add_post_event');
            }

            $option['lasttime'] = time();
		    update_option( "piadas_cg_option", $option );
        }

        $categories = get_option("piadas-cg_categories");
		if($_POST['cat_adulto'] == "Yes") $categories['Adulto'] = true;
        else $categories['Adulto'] = false;

		if($_POST['cat_amizade'] == "Yes") $categories['Amizade'] = true;
        else $categories['Amizade'] = false;

		if($_POST['cat_animais'] == "Yes") $categories['Animais'] = true;
        else $categories['Animais'] = false;

		if($_POST['cat_argentino'] == "Yes") $categories['Argentino'] = true;
        else $categories['Argentino'] = false;

		if($_POST['cat_azarados'] == "Yes") $categories['Azarados'] = true;
        else $categories['Azarados'] = false;

		if($_POST['cat_bar'] == "Yes") $categories['Bar'] = true;
        else $categories['Bar'] = false;

		if($_POST['cat_bebados'] == "Yes") $categories['Bêbados'] = true;
        else $categories['Bêbados'] = false;

		if($_POST['cat_bichas'] == "Yes") $categories['Bichas'] = true;
        else $categories['Bichas'] = false;

		if($_POST['cat_burros'] == "Yes") $categories['Burros'] = true;
        else $categories['Burros'] = false;

		if($_POST['cat_caipiras'] == "Yes") $categories['Caipiras'] = true;
        else $categories['Caipiras'] = false;

		if($_POST['cat_casais'] == "Yes") $categories['Casais'] = true;
        else $categories['Casais'] = false;

		if($_POST['cat_cornos'] == "Yes") $categories['Cornos'] = true;
        else $categories['Cornos'] = false;

		if($_POST['cat_curtas'] == "Yes") $categories['Curtas'] = true;
        else $categories['Curtas'] = false;

		if($_POST['cat_escola'] == "Yes") $categories['Escola'] = true;
        else $categories['Escola'] = false;

		if($_POST['cat_esportes'] == "Yes") $categories['Esportes'] = true;
        else $categories['Esportes'] = false;

		if($_POST['cat_familia'] == "Yes") $categories['Familia'] = true;
        else $categories['Familia'] = false;

		if($_POST['cat_fodidos'] == "Yes") $categories['Fodidos'] = true;
        else $categories['Fodidos'] = false;

		if($_POST['cat_gagos'] == "Yes") $categories['Gagos'] = true;
        else $categories['Gagos'] = false;

		if($_POST['cat_humor_negro'] == "Yes") $categories['Humor Negro'] = true;
        else $categories['Humor Negro'] = false;

		if($_POST['cat_idosos'] == "Yes") $categories['Idosos'] = true;
        else $categories['Idodos'] = false;

		if($_POST['cat_informatica'] == "Yes") $categories['Informatica'] = true;
        else $categories['Informatica'] = false;

		if($_POST['cat_joaozinho'] == "Yes") $categories['Joãozinho'] = true;
        else $categories['Joãozinho'] = false;

		if($_POST['cat_ladroes'] == "Yes") $categories['Ladrões'] = true;
        else $categories['Ladrões'] = false;

		if($_POST['cat_loiras'] == "Yes") $categories['Loiras'] = true;
        else $categories['Loiras'] = false;

		if($_POST['cat_loucos'] == "Yes") $categories['Loucos'] = true;
        else $categories['Loucos'] = false;

		if($_POST['cat_medicos'] == "Yes") $categories['Medicos'] = true;
        else $categories['Medicos'] = false;

		if($_POST['cat_mineiro'] == "Yes") $categories['Mineiro'] = true;
        else $categories['Mineiro'] = false;

		if($_POST['cat_morte'] == "Yes") $categories['Morte'] = true;
        else $categories['Morte'] = false;

		if($_POST['cat_mulheres'] == "Yes") $categories['Mulheres'] = true;
        else $categories['Mulheres'] = false;

		if($_POST['cat_outros'] == "Yes") $categories['Outros'] = true;
        else $categories['Outros'] = false;

		if($_POST['cat_papagaio'] == "Yes") $categories['Papagaio'] = true;
        else $categories['Papagaio'] = false;

		if($_POST['cat_politicos'] == "Yes") $categories['Politicos'] = true;
        else $categories['Politicos'] = false;

		if($_POST['cat_portugues'] == "Yes") $categories['Português'] = true;
        else $categories['Português'] = false;

		if($_POST['cat_portugueses'] == "Yes") $categories['Portugueses'] = true;
        else $categories['Portugueses'] = false;

		if($_POST['cat_profissoes'] == "Yes") $categories['Profissões'] = true;
        else $categories['Profissões'] = false;

		if($_POST['cat_racas'] == "Yes") $categories['Raças'] = true;
        else $categories['Raças'] = false;

		if($_POST['cat_regionais'] == "Yes") $categories['Regionais'] = true;
        else $categories['Regionais'] = false;

		if($_POST['cat_religiao'] == "Yes") $categories['Religião'] = true;
        else $categories['Religião'] = false;

		if($_POST['cat_sexo'] == "Yes") $categories['Sexo'] = true;
        else $categories['Sexo'] = false;

		if($_POST['cat_sogra'] == "Yes") $categories['Sogra'] = true;
        else $categories['Sogra'] = false;

		if($_POST['cat_sogras'] == "Yes") $categories['Sogras'] = true;
        else $categories['Sogras'] = false;

		if($_POST['cat_transito'] == "Yes") $categories['Trânsito'] = true;
        else $categories['Trânsito'] = false;

		if($_POST['cat_velhos'] == "Yes") $categories['Velhos'] = true;
        else $categories['Velhos'] = false;

        // Save the posted value in the database
		update_option( "piadas-cg_categories", $categories );

        // Put an options updated message on the screen

?>
<div class="updated"><p><strong>Opções salvas.</strong></p></div>
<?php

    }

    // Now display the options editing screen

    echo '<div class="wrap">';


    // header
    echo "<h2>" . __( 'Opções Piadas ClickGrátis', 'mt_trans_domain' ) . "</h2>";
$blog_url_feedback=get_bloginfo('url');
	

    // options form
    $categories = get_option("piadas-cg_categories");
    
    $activated = get_option("schedule_activated");

    if ($activated=="Yes" || $activated=="") {
    $activated="checked";
    $activated1="";
    } else {
    $activated="";
    $activated1="checked";
    }

    ?>
<form name="form1" method="post" action="">
<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">

<p><b>Categorias:</b>
<ul>
<li><input type="checkbox" name="cat_adulto" value="Yes" <?php echo ($categories['Adulto']==true) ? "checked" : ""; ?>>Adulto</li>
<li><input type="checkbox" name="cat_amizade" value="Yes" <?php echo ($categories['Amizade']==true) ? "checked" : ""; ?>>Amizade</li>
<li><input type="checkbox" name="cat_animais" value="Yes" <?php echo ($categories['Animais']==true) ? "checked" : ""; ?>>Animais</li>
<li><input type="checkbox" name="cat_argentino" value="Yes" <?php echo ($categories['Argentino']==true) ? "checked" : ""; ?>>Argentino</li>
<li><input type="checkbox" name="cat_azarados" value="Yes" <?php echo ($categories['Azarados']==true) ? "checked" : ""; ?>>Azarados</li>
<li><input type="checkbox" name="cat_bar" value="Yes" <?php echo ($categories['Bar']==true) ? "checked" : ""; ?>>Bar</li>
<li><input type="checkbox" name="cat_bebados" value="Yes" <?php echo ($categories['Bêbados']==true) ? "checked" : ""; ?>>Bêbados</li>
<li><input type="checkbox" name="cat_bichas" value="Yes" <?php echo ($categories['Bichas']==true) ? "checked" : ""; ?>>Bichas</li>
<li><input type="checkbox" name="cat_burros" value="Yes" <?php echo ($categories['Burros']==true) ? "checked" : ""; ?>>Burros</li>
<li><input type="checkbox" name="cat_caipiras" value="Yes" <?php echo ($categories['Caipiras']==true) ? "checked" : ""; ?>>Caipiras</li>
<li><input type="checkbox" name="cat_casais" value="Yes" <?php echo ($categories['Casais']==true) ? "checked" : ""; ?>>Casais</li>
<li><input type="checkbox" name="cat_cornos" value="Yes" <?php echo ($categories['Cornos']==true) ? "checked" : ""; ?>>Cornos</li>
<li><input type="checkbox" name="cat_curtas" value="Yes" <?php echo ($categories['Curtas']==true) ? "checked" : ""; ?>>Curtas</li>
<li><input type="checkbox" name="cat_escola" value="Yes" <?php echo ($categories['Escola']==true) ? "checked" : ""; ?>>Escola</li>
<li><input type="checkbox" name="cat_esportes" value="Yes" <?php echo ($categories['Esportes']==true) ? "checked" : ""; ?>>Esportes</li>
<li><input type="checkbox" name="cat_familia" value="Yes" <?php echo ($categories['Familia']==true) ? "checked" : ""; ?>>Familia</li>
<li><input type="checkbox" name="cat_fodidos" value="Yes" <?php echo ($categories['Fodidos']==true) ? "checked" : ""; ?>>Fodidos</li>
<li><input type="checkbox" name="cat_gagos" value="Yes" <?php echo ($categories['Gagos']==true) ? "checked" : ""; ?>>Gagos</li>
<li><input type="checkbox" name="cat_humor_negro" value="Yes" <?php echo ($categories['Humor Negro']==true) ? "checked" : ""; ?>>Humor Negro</li>
<li><input type="checkbox" name="cat_idosos" value="Yes" <?php echo ($categories['Idosos']==true) ? "checked" : ""; ?>>Idosos</li>
<li><input type="checkbox" name="cat_informatica" value="Yes" <?php echo ($categories['Informatica']==true) ? "checked" : ""; ?>>Informatica</li>
<li><input type="checkbox" name="cat_joaozinho" value="Yes" <?php echo ($categories['Joãozinho']==true) ? "checked" : ""; ?>>Joãozinho</li>
<li><input type="checkbox" name="cat_ladroes" value="Yes" <?php echo ($categories['Ladrões']==true) ? "checked" : ""; ?>>Ladrões</li>
<li><input type="checkbox" name="cat_loiras" value="Yes" <?php echo ($categories['Loiras']==true) ? "checked" : ""; ?>>Loiras</li>
<li><input type="checkbox" name="cat_loucos" value="Yes" <?php echo ($categories['Loucos']==true) ? "checked" : ""; ?>>Loucos</li>
<li><input type="checkbox" name="cat_medicos" value="Yes" <?php echo ($categories['Medicos']==true) ? "checked" : ""; ?>>Medicos</li>
<li><input type="checkbox" name="cat_mineiro" value="Yes" <?php echo ($categories['Mineiro']==true) ? "checked" : ""; ?>>Mineiro</li>
<li><input type="checkbox" name="cat_morte" value="Yes" <?php echo ($categories['Morte']==true) ? "checked" : ""; ?>>Morte</li>
<li><input type="checkbox" name="cat_mulheres" value="Yes" <?php echo ($categories['Mulheres']==true) ? "checked" : ""; ?>>Mulheres</li>
<li><input type="checkbox" name="cat_outros" value="Yes" <?php echo ($categories['Outros']==true) ? "checked" : ""; ?>>Outros</li>
<li><input type="checkbox" name="cat_papagaio" value="Yes" <?php echo ($categories['Papagaio']==true) ? "checked" : ""; ?>>Papagaio</li>
<li><input type="checkbox" name="cat_politicos" value="Yes" <?php echo ($categories['Politicos']==true) ? "checked" : ""; ?>>Politicos</li>
<li><input type="checkbox" name="cat_portugues" value="Yes" <?php echo ($categories['Português']==true) ? "checked" : ""; ?>>Português</li>
<li><input type="checkbox" name="cat_portugueses" value="Yes" <?php echo ($categories['Portugueses']==true) ? "checked" : ""; ?>>Portugueses</li>
<li><input type="checkbox" name="cat_profissoes" value="Yes" <?php echo ($categories['Profissões']==true) ? "checked" : ""; ?>>Profissões</li>
<li><input type="checkbox" name="cat_racas" value="Yes" <?php echo ($categories['Raças']==true) ? "checked" : ""; ?>>Raças</li>
<li><input type="checkbox" name="cat_regionais" value="Yes" <?php echo ($categories['Regionais']==true) ? "checked" : ""; ?>>Regionais</li>
<li><input type="checkbox" name="cat_religiao" value="Yes" <?php echo ($categories['Religião']==true) ? "checked" : ""; ?>>Religião</li>
<li><input type="checkbox" name="cat_sexo" value="Yes" <?php echo ($categories['Sexo']==true) ? "checked" : ""; ?>>Sexo</li>
<li><input type="checkbox" name="cat_sogra" value="Yes" <?php echo ($categories['Sogra']==true) ? "checked" : ""; ?>>Sogra</li>
<li><input type="checkbox" name="cat_sogras" value="Yes" <?php echo ($categories['Sogras']==true) ? "checked" : ""; ?>>Sogras</li>
<li><input type="checkbox" name="cat_transito" value="Yes" <?php echo ($categories['Trânsito']==true) ? "checked" : ""; ?>>Trânsito</li>
<li><input type="checkbox" name="cat_velhos" value="Yes" <?php echo ($categories['Velhos']==true) ? "checked" : ""; ?>>Velhos</li>
</ul></p>
<p><b>Post automático:</b>
<input type="text" name="interval" value="<? echo $option['interval'];?>" /> horas (0 para desabilitar)</p>

<p class="submit">
<input type="submit" name="Submit" value="Atualizar opções" />
</p><hr />
<?
    $lt = (empty($option['lasttime'])) ? 0 : $option['lasttime'];
    $inter = (empty($option['interval'])) ? 0 : $option['interval'];

    if($inter == 0) {
        printf("Post automático desabilitado.");
    } else {
        printf("Último post piada automático: %s", date("d-m-Y H:i:s", $lt));
        print '<br />';
        printf("Próximo post piada automático: %s", date("d-m-Y H:i:s", $lt+($inter*60*60)));
    }
?>
</form>
<script type="text/javascript">
function validate_required(field,alerttxt)
{
with (field)
  {
  if (value==null||value=="")
    {
    alert(alerttxt);return false;
    }
  else
    {
    return true;
    }
  }
}

function validate_form(thisform)
{
with (thisform)
  {
  if (validate_required(subject,"Subject must be filled out!")==false)
  {email.focus();return false;}
  if (validate_required(email,"E-Mail must be filled out!")==false)
  {email.focus();return false;}
  if (validate_required(feedback,"Feedback must be filled out!")==false)
  {email.focus();return false;}
  }
}
</script>
<?/*<h3>Submit Feedback about my Plugin!</h3>
<p><b>Note: Only send feedback in english, I cannot understand other languages!</b></p>
<form name="form2" method="post" action="" onsubmit="return validate_form(this)">
<p><?php _e("Name:", 'mt_trans_domain' ); ?> 
<input type="text" name="name" /></p>
<p><?php _e("E-Mail:", 'mt_trans_domain' ); ?> 
<input type="text" name="email" /></p>
<p><?php _e("Category:", 'mt_trans_domain'); ?>
<select name="category">
<option value="Bug Report">Bug Report</option>
<option value="Feature Request">Feature Request</option>
<option value="Other">Other</option>
</select>
<p><?php _e("Subject (Required):", 'mt_trans_domain' ); ?>
<input type="text" name="subject" /></p>
<input type="checkbox" name="response" value="Yes" /> I want e-mailing back about this feedback</p>
<p><?php _e("Comment (Required):", 'mt_trans_domain' ); ?> 
<textarea name="feedback"></textarea>
</p>
<p class="submit">
<input type="submit" name="Send" value="<?php _e('Send', 'mt_trans_domain' ); ?>" />
</p><hr /></form>
</div>
<?php */} ?>
<?php
if (get_option("jr_Digg_links_choice")=="") {
//Digg_choice();
}



?>
