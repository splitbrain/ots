<?php
$OTS = '/opt/bin/ots';

loadurl();

if (!isset($_REQUEST['format']) || $_REQUEST['format'] = !'text') {
    header('Content-Type: text/html; charset=utf-8');

    html_header();
    form();
    if (!empty($_REQUEST['text']) || !empty($_REQUEST['url'])) {
        echo '<article id="out">';
        echo nl2br(htmlspecialchars(ots()));
        echo '</article>';
    }
    html_footer();

} else {
    header('Content-Type: text/plain; charset=utf-8');
    if (!empty($_REQUEST['text']) || !empty($_REQUEST['url'])) {
        echo ots();
    }
}

function loadurl()
{
    if (empty($_REQUEST['url'])) return;
    $text = file_get_contents($_REQUEST['url']);
    if (preg_match('#(<body[^>]*>)(.*)(</body>)#si', $text, $match)) {
        $text = $match[2];
    }
    $text = preg_replace('#(<script).*?(</script>)#si', '', $text);
    $text = preg_replace('#(<style).*?(</style>)#si', '', $text);
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    $text = preg_replace('/^\s*/m', '', $text);
    $text = preg_replace('/\s*$/m', '', $text);
    $text = preg_replace('/[\r\n]\s*[\r\n]/m', "\n", $text);

    $_REQUEST['text'] = $text;
    unset($_REQUEST['url']);
}

function ots()
{
    global $OTS;

    // store text in a tempfile - I'm to lazy for procopen stuff ;-)
    $tmpfile = tempnam(sys_get_temp_dir(), 'OTS');
    if (!empty($_REQUEST['text'])) {
        file_put_contents($tmpfile, $_REQUEST['text']);
    }

    // prepare command
    $cmd = $OTS;
    if (isset($_REQUEST['type']) && $_REQUEST['type'] == 'tag') {
        $cmd .= ' -a';
    }
    if (isset($_REQUEST['ratio'])) {
        $cmd .= ' -r ' . ((int)$_REQUEST['ratio']);
    }
    if (isset($_REQUEST['lang'])) {
        $cmd .= ' -d ' . escapeshellarg($_REQUEST['lang']);
    }
    $cmd .= ' ' . escapeshellarg($tmpfile);

    // execute
    exec($cmd, $output, $return);

    // remove temporary file
    unlink($tmpfile);

    if ($return != 0) return "!!!ERROR: OTS failed";
    return join("\n", $output);
}

function form()
{
    echo '<form action="#out" method="post">';

    echo '<fieldset>';
    echo '<legend>Text input:</legend>';
    echo '<textarea name="text" id="text" rows="5" cols="80" placeholder="Some long text">' .
        ((!empty($_REQUEST['text'])) ? htmlspecialchars($_REQUEST['text']) : '') .
        '</textarea>';
    echo '<br />';

    echo '<label for="url">Or load from URL:</label>';
    echo '<input type="text" name="url" id="url" placeholder="https://example.com" value="' .
        ((!empty($_REQUEST['url'])) ? htmlspecialchars($_REQUEST['url']) : '') . '" />';

    echo '</fieldset>';

    echo '<fieldset>';
    echo '<legend>Output</legend>';
    echo inp_radio('type', 'sum', 'Summary', true);
    echo inp_radio('type', 'tag', 'Keywords');
    echo '</fieldset>';

    echo '<fieldset>';
    echo '<legend>Summarization Ratio</legend>';
    echo inp_radio('ratio', '5', '5%');
    echo inp_radio('ratio', '10', '10%');
    echo inp_radio('ratio', '20', '20%', true);
    echo inp_radio('ratio', '30', '30%');
    echo inp_radio('ratio', '40', '40%');
    echo inp_radio('ratio', '50', '50%');
    echo inp_radio('ratio', '60', '60%');
    echo inp_radio('ratio', '70', '70%');
    echo inp_radio('ratio', '80', '80%');
    echo '</fieldset>';

    echo '<fieldset>';
    echo '<legend>Language</legend>';
    echo inp_dropdown('lang', explode(' ', 'bg ca cs cy da de el en eo es et eu fi fr ga gl he hu ia id is it lv mi ms mt nl nn pl pt ro ru sv tl tr uk yi'), 'en');
    echo '</fieldset>';

    echo '<input type="submit" />';
    echo '</form>';
}

function inp_radio($name, $val, $label, $isdefault = false)
{
    $checked = false;
    if (!empty($_REQUEST[$name])) {
        if ($_REQUEST[$name] == $val) {
            $checked = true;
        }
    } elseif ($isdefault) {
        $checked = true;
    }

    return '<input type="radio" name="' . htmlspecialchars($name) . '" ' .
        'id="' . htmlspecialchars($name) . '_' . htmlspecialchars($val) . '" ' .
        'value="' . htmlspecialchars($val) . '" ' .
        ($checked ? 'checked="checked"' : '') .
        '/><label for="' . htmlspecialchars($name) . '_' .
        htmlspecialchars($val) . '">' . htmlspecialchars($label) . '</label>';
}

function inp_dropdown($name, $vals, $default)
{
    $out = '<select id="' . htmlspecialchars($name) . '" ' .
        'name="' . htmlspecialchars($name) . '">';
    foreach ($vals as $val) {
        $checked = false;
        if (!empty($_REQUEST[$name])) {
            if ($_REQUEST[$name] == $val) {
                $checked = true;
            }
        } elseif ($val == $default) {
            $checked = true;
        }
        $out .= '<option value="' . htmlspecialchars($val) . '" ' . ($checked ? 'selected="selected"' : '') . '>' .
            htmlspecialchars($val) . '</option>';
    }
    $out .= '</select>';
    return $out;
}

function html_header()
{
    ?>
    <html lang="en">
    <head>
        <title>OTS</title>
        <meta http-equiv="Content-type" content="text/html;charset=UTF-8"/>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
    </head>
    <body>
    <div class="container">
    <h1>Text Summarizer</h1>
    <?php
}

function html_footer()
{
    ?>
    <footer>
        <a href="https://https://github.com/splitbrain/ots">Webinterface</a> for
        <a href="https://github.com/neopunisher/Open-Text-Summarizer">Open Text Summarizer</a>
    </footer>
    </div>
    </body>
    </html>
    <?php
}
