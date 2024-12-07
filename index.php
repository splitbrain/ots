<?php
$OTS = '/usr/bin/ots';

loadurl();

if(!isset($_REQUEST['format']) || $_REQUEST['format'] =! 'text'){
    header('Content-Type: text/html; charset=utf-8');

    html_header();
    form();
    if(!empty($_REQUEST['text']) || !empty($_REQUEST['url'])){
        echo '<div id="out">';
        echo nl2br(htmlspecialchars(ots()));
        echo '</div>';
    }
    html_footer();

}else{
    header('Content-Type: text/plain; charset=utf-8');
    if(!empty($_REQUEST['text']) || !empty($_REQUEST['url'])){
        echo ots();
    }
}

function loadurl(){
    if(empty($_REQUEST['url'])) return;
    $text = file_get_contents($_REQUEST['url']);
    if(preg_match('#(<body[^>]*>)(.*)(</body>)#si',$text,$match)){
        $text = $match[2];
    }
    $text = preg_replace('#(<script).*?(</script>)#si','',$text);
    $text = preg_replace('#(<style).*?(</style>)#si','',$text);
    $text = strip_tags($text);
    $text = html_entity_decode($text,ENT_QUOTES,'UTF-8');
    $text = preg_replace('/^\s*/m','',$text);
    $text = preg_replace('/\s*$/m','',$text);
    $text = preg_replace('/[\r\n]\s*[\r\n]/m',"\n",$text);

    $_REQUEST['text'] = $text;
    unset($_REQUEST['url']);
}

function ots(){
    global $OTS;

    // store text in a tempfile - I'm to lazy for procopen stuff ;-)
    $tmpfile = tempnam(sys_get_temp_dir(),'OTS');
    if(!empty($_REQUEST['text'])){
        file_put_contents($tmpfile,$_REQUEST['text']);
    }

    // prepare command
    $cmd = $OTS;
    if(isset($_REQUEST['type']) && $_REQUEST['type'] == 'tag'){
        $cmd .= ' -a';
    }
    if(isset($_REQUEST['ratio'])){
        $cmd .= ' -r '.((int) $_REQUEST['ratio']);
    }
    if(isset($_REQUEST['lang'])){
        $cmd .= ' -d '.escapeshellarg($_REQUEST['lang']);
    }
    $cmd .= ' '.escapeshellarg($tmpfile);

    // execute
    exec($cmd,$output,$return);

    // remove temporary file
    unlink($tmpfile);

    if($return != 0) return "!!!ERROR: OTS failed";
    return join("\n",$output);
}

function form(){
    echo '<form action="#out" method="post">';

    echo '<fieldset>';
    echo '<legend>Input</legend>';
    echo '<textarea name="text" id="text" rows="5" cols="80">'.
         ((!empty($_REQUEST['text'])) ? htmlspecialchars($_REQUEST['text']) : '').
         '</textarea>';
    echo '<br />';
    echo '<input type="text" name="url" id="url" value="'.
         ((!empty($_REQUEST['url'])) ? htmlspecialchars($_REQUEST['url']) : '').'" />';
    echo '<label for="url">(or load from URL)</label>';
    echo '</fieldset>';

    echo '<fieldset>';
    echo '<legend>Output</legend>';
    echo inp_radio('type','sum','Summary',true);
    echo inp_radio('type','tag','Keywords');
    echo '</fieldset>';

    echo '<fieldset>';
    echo '<legend>Summarization Ratio</legend>';
    echo inp_radio('ratio','5','5%');
    echo inp_radio('ratio','10','10%');
    echo inp_radio('ratio','20','20%',true);
    echo inp_radio('ratio','30','30%');
    echo inp_radio('ratio','40','40%');
    echo inp_radio('ratio','50','50%');
    echo inp_radio('ratio','60','60%');
    echo inp_radio('ratio','70','70%');
    echo inp_radio('ratio','80','80%');
    echo '</fieldset>';

    echo '<fieldset>';
    echo '<legend>Language</legend>';
    echo inp_dropdown('lang',explode(' ','bg ca cs cy da de el en eo es et eu fi fr ga gl he hu ia id is it lv mi ms mt nl nn pl pt ro ru sv tl tr uk yi'),'en');
    echo '</fieldset>';

    echo '<input type="submit" />';
    echo '</form>';
}

function inp_radio($name, $val, $label,$isdefault=false){
    $checked = false;
    if(!empty($_REQUEST[$name])){
        if($_REQUEST[$name] == $val){
            $checked = true;
        }
    }elseif($isdefault){
        $checked = true;
    }

    return '<input type="radio" name="'.htmlspecialchars($name).'" '.
           'id="'.htmlspecialchars($name).'_'.htmlspecialchars($val).'" '.
           'value="'.htmlspecialchars($val).'" '.
           ($checked ? 'checked="checked"' : '').
           '/><label for="'.htmlspecialchars($name).'_'.
           htmlspecialchars($val).'">'.htmlspecialchars($label).'</label>';
}

function inp_dropdown($name, $vals, $default){
    $out = '<select id="'.htmlspecialchars($name).'" '.
           'name="'.htmlspecialchars($name).'">';
    foreach($vals as $val){
        $checked = false;
        if(!empty($_REQUEST[$name])){
            if($_REQUEST[$name] == $val){
                $checked = true;
            }
        }elseif($val == $default){
            $checked = true;
        }
        $out .= '<option value="'.htmlspecialchars($val).'" '.($checked ? 'selected="selected"' : '').'>'.
                htmlspecialchars($val).'</option>';
    }
    $out .= '</select>';
    return $out;
}

function html_header(){
    echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"> ';
    ?>
    <html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
    <head>
        <title>OTS</title>
        <meta http-equiv="Content-type" content="text/html;charset=UTF-8" />

        <style type="text/css">
            * {
                padding: 0;
                margin: 0;
                font-family: Arial, sans-serif;
            }

            body {
                color: #000;
                background-color: #fff;
                padding: 1em;
            }

            a, h1 {
                color: #6A8AD4;
            }

            h1 {
                font-size: 120%
            }

            fieldset, input, textarea {
                border: 1px solid #6A8AD4;
            }

            fieldset, div, p {
                padding: 1em;
                margin: 1em 0;
            }

            label {
                margin-left: 0.1em;
                margin-right: 0.5em;
            }

        </style>
    </head>
    <body>
    <h1>Text Summarizer</h1>
    <?php
}

function html_footer(){
    echo '<p><small>';
    echo '<a href="http://gist.github.com/399029">Webinterface</a> for <a href="http://libots.sourceforge.net/">Open Text Summarizer</a>';
    echo '</small></p>';
    echo '</body>';
    echo '</html>';
}
