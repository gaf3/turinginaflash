<?php

  session_start();

  if (preg_match('/Opera/', $_SERVER['HTTP_USER_AGENT'], $log_version))
    $browser = 'OPERA';
  else if (preg_match('/MSIE ([0-9].[0-9]{1,2})/', $_SERVER['HTTP_USER_AGENT'], $log_version))
    $browser = 'IE';
  else
    $browser = 'OTHER';

  $mime_type = ($browser == 'IE' || $browser == 'OPERA')
             ? 'application/octetstream'
             : 'application/octet-stream';

  switch ($_REQUEST['task']) {

    case 'view_machine':

      display_machine($_SESSION['stored_machine']);
      break;

    case 'store_machine':

      $_SESSION['stored_machine'] = $_REQUEST['store_machine'];
      xml_action('stored_machine');
      break;

    case 'store_tape':

      $_SESSION['stored_tape'] = $_REQUEST['store_tape'];
      xml_action('stored_tape');
      break;

    case 'save_machine':

      save_header($mime_type,"machine.tml");
    
      if ($_SESSION['stored_machine'])
        print($_SESSION['stored_machine']); 
      else
        xml_action('empty_machine');
      break;

    case 'save_tape':

      save_header($mime_type,"tape.tml");
    
      if ($_SESSION['stored_tape'])
        print($_SESSION['stored_tape']);
      else
        xml_action('empty_tape');
      break;

    case 'open_machine':

      open_form('machine');
      break;

    case 'open_tape':

      open_form('tape');
      break;

    case 'opened_machine':

      if (is_uploaded_file($_FILES['machine_file']['tmp_name']) && ($_REQUEST['open'] == 'Open'))
        $_SESSION['stored_machine'] = file_get_contents($_FILES['machine_file']['tmp_name']);

      display_flash();
      break;

    case 'opened_tape':

      if (is_uploaded_file($_FILES['tape_file']['tmp_name']) && ($_REQUEST['open'] == 'Open'))
        $_SESSION['stored_tape'] = file_get_contents($_FILES['tape_file']['tmp_name']);

      display_flash();
      break;

    default:

      display_flash();
      break;

  }



  function xml_action($action) {

    print('<' . '?xml version="1.0" encoding="UTF-8" ?' . '>' . "\n"); 
    print("<turing action='$action' />\n"); 

  }



  function save_header($mime_type,$file) {

    header('Content-Type: ' . $mime_type);
    header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Pragma: private');
    header('Cache-control: private, must-revalidate');
    header('Content-Disposition: attachment; filename="' . $file . '"');

  }



  function open_form($type) {

    if ($type == "tape")
      $color = "#E7BC78";
    else
      $color = "#C4AADF";

    start_html("Open " . ucfirst($type),$color);
?>
    <p><br>Click <b>Browse</b> to search for the file on your computer.<br>
    Once found, click <b>Open</b> to load it into Turing in a <i>Flash</i>. <br>
    To return to Turing in a <i>Flash</i> with opening a file, click <b>Cancel</b>.
    <form method='post' enctype='multipart/form-data' action='/index.php'>
      <b>File:</b> <input type='file' name='<?php print $type; ?>_file'>
      <input type="hidden" name="MAX_FILE_SIZE" value="2500000" />
      <input type='hidden' name='task' value='opened_<?php print $type; ?>'>
      <input type='submit' name='open' value='Open'>
      <input type='submit' name='open' value='Cancel'>
    </form>
<?php
    end_html("Open " . ucfirst($type),$color);

  }



  function display_flash() {

    $flash = "turing.swf";

?>
<html>
  <head>
    <title>Turing in a Flash</title>
  </head>
  <body>
    <center>
      <object width="910" height="670">
        <param name="movie" value="<?php print $flash; ?>">
        <embed src="<?php print $flash; ?>" width="910" height="670"></embed>
      </object>  
    </center>
  </body>
</html>
<?php
  }



  function display_machine($xml) {

    start_html("View Comments","#D5AAAA");

    $states = array();

    preg_match("/<states>(.*)<\\/states>/is", $xml, $state_matches);

    $state_xmls = split("</state>",$state_matches[1]);
    array_pop($state_xmls);

    foreach ($state_xmls as $state_xml) {

      preg_match("/index=['\"]?(\\d+)['\"]?/is", $state_xml, $index_matches);
      $state_index = $index_matches[1];

      preg_match("/value=['\"]?(\\d+)['\"]?/is", $state_xml, $value_matches);
      $state_value = $value_matches[1];

      preg_match("/<description>(.*)<\\/description>/is", $state_xml, $descr_matches);
      $state_descr = preg_replace("/&apos;/","'",$descr_matches[1]);

      $states[$state_index] = array(
        'value' => $state_value,
        'descr' => $state_descr,
        'froms' => array(),
        'tos' => array()
      );

    }

    preg_match("/<links>(.*)<\\/links>/is", $xml, $link_matches);

    $link_xmls = split("</link>",$link_matches[1]);
    array_pop($link_xmls);

    foreach ($link_xmls as $link_xml) {

      preg_match("/index=['\"]?(\\d+)['\"]?/is", $link_xml, $index_matches);
      $link_index = $index_matches[1];

      preg_match("/<description>(.*)<\\/description>/is", $link_xml, $descr_matches);
      $link_descr = preg_replace("/&apos;/","'",$descr_matches[1]);

      preg_match("/<condition>(.*)<\\/condition>/is", $link_xml, $cond_matches);
      $link_cond = $cond_matches[1];

      preg_match("/<reaction>(.*)<\\/reaction>/is", $link_xml, $react_matches);
      $link_react = $react_matches[1];

      if (preg_match("/<from>.*<state[^>]*index=['\"]?(\\d+)['\"]?[^>]*>.*<\\/from>/is", $link_xml, $from_matches))
        $link_from = $from_matches[1];
      else
        $link_from = -1;

      if (preg_match("/<to>.*<state[^>]*index=['\"]?(\\d+)['\"]?[^>]*>.*<\\/to>/is", $link_xml, $to_matches))
        $link_to = $to_matches[1];
      else
        $link_to = -1;

      $links[$link_index] = array(
        'cond' => $link_cond,
        'react' => $link_react,
        'descr' => $link_descr,
        'from' => $link_from,
        'to' => $link_to
      );

      if ($link_from > -1)
        $states[$link_from]['tos'][] = $links[$link_index];

      if ($link_to > 1)
        $states[$link_to]['froms'][] = $links[$link_index];

    }

?>
    <table class='clean' width='570'>
      <tr>
        <td align='center' valign='top' width='40' height='5'></td>
        <td align='center' valign='top' width='30' height='5'></td>
        <td align='center' valign='top' width='500' height='5'></td>
      </tr>
<?php
    foreach ($states as $state_index=>$state) {
?>
      <tr>
        <td align='right' valign='top' width='40'>
          <table class='clean' background='state.jpg' width='32' height='32'>
            <tr>
              <td align='center' valign='middle'>
                <?php print "$state[value]\n"; ?>
              </td>
            </tr>
          </table>
        </td>
        <td align='left' valign='middle' colspan='2' width='530'>
          <?php print "$state[descr]\n"; ?>
        </td>
      </tr>
<?php
      foreach ($state['tos'] as $link) {
?>
      <tr>
        <td align='right' valign='top' colspan='2' width='70'>
          <table class='clean' background='link.jpg' width='56' height='32'>
            <tr>
              <td align='center' valign='middle' width='29'>
                <?php print "$link[cond]\n"; ?>
              </td>
              <td align='center' valign='middle' width='27'>
                <?php print "$link[react]\n"; ?>
              </td>
            </tr>
          </table>
        </td>
        <td align='left' valign='middle' width='500'>
          <?php print "$link[descr]\n"; ?>
        </td>
      </tr>
<?php
      }
?>
<?php
    }
?>
    </table>
<?php

    end_html("View Comments");

  }



  function start_html($title,$color="#C4AADF") {
?>
<html>
  <head>
    <title><?php print $title; ?></title>
    <style>

      body {
        font-family: Arial;
        font-size: 12pt;
        background-color: #FFFFFF;
        color: #000000;
        margin: 0px;
        margin-top: 0;
        margin-left: 0;
        margin-right: 0;
        margin-bottom: 0;
      }

      table.clean {
        border: none;
      }

      table {
        border: solid;
        border-color: #000000;
        border-width: 1;
      }

      th,
      td {
        padding: 3px;
        border-collapse: collapse; 
        color: #000000;
        font-family: Arial;
        font-size: 12pt;
      }

    </style>
  </head>
  <body>
    <center>
    <br><br>
    <table bgcolor="#9F221C" width='600'>
      <tr>
        <td align='center' valign='top' height='40'>
          <table bgcolor="#2D296D" height='30' width='590' >
            <tr>
              <td align='left'><font size="3" color='white'><b>Turing in a <i>Flash</i></b></font></td>
              <td align='right'><font size="3" color='white'><b><?php print $title; ?></b></font></td>
            </tr>
          </table>
        </td>
      </tr>
      <tr>
        <td width='590'>
          <table bgcolor="#719DC2" width='590' >
            <tr>
              <td align='left'><font color='white'>
                <table bgcolor="<?php print $color; ?>" width='580' >
                  <tr>
                    <td align='center' valign='top'>
<?php
  }



  function end_html($title) {
?>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
    <center>
  </body>
</html>


<?php
  }
?>