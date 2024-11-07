<?php

$moss_url = 'http://moss.stanford.edu/results';
$curl = null;

function parse_index($programmingid, $index_file, $max, $lowest) {
    global $CFG, $DB;

    $lines = fetch_by_curl($index_file);
    $s = 0;

    foreach ($lines as $line) {
        $m = array();
        switch ($s) {
        case 0:
            if (preg_match('/<TABLE>/', $line)) {
                $s = 1;
                $c = 0;
            }
            break;
        case 1:
            if (preg_match('/^<TR><TD><A HREF="([^"]*)">(\d*)?-(\d*)\.\w* \((\d*)%\)<\/A>/', $line, $m)) {
                $resemble = new object;
                $resemble->programmingid = $programmingid;

                $resemble->submitid1 = $m[3];
                $resemble->percent1 = $m[4];

                $s = 2;
            }
            break;
        case 2:
            if (preg_match('/<TD><A HREF="([^"]*)">(\d*)?-(\d*)\.\w* \((\d*)%\)<\/A>/', $line, $m)) {
                $resemble->submitid2 = $m[3];
                $resemble->percent2 = $m[4];
                $s = 3;
            }
            break;
        case 3:
            if (preg_match('/<TD ALIGN=right>(\d+)/', $line, $m)) {
                $resemble->matchedcount = $m[1];
                if ($resemble->percent1 > $lowest or $resemble->percent2 > $lowest) {
                    $resemble->matchedlines = parse_lines($index_file.'/match'.$c.'-top.html');
                    if (!$DB->insert_record('programming_resemble', $resemble)) {
                        printf("Failed to insert record.\n");
                    }
                }
                $c ++;
                $s = 1;
            }
            break;
        }
    }
}

function parse_lines($topfile) {
    $lines = fetch_by_curl($topfile);
    $s = 0;
    $c = 0;
    $result = '';
    
    foreach($lines as $line) {
        $m = array();
        switch ($s) {
        case 0:
            if (preg_match('/^<TR><TD><A[^>]*>(\d+-\d+)<\/A>/', $line, $m)) {
                $s = 1;
                if ($result != '') $result .= ';';
                $result .= $m[1].',';
            }
            break;
        case 1:
            if (preg_match('/^<TD><A[^>]*>(\d+-\d+)<\/A>/', $line, $m)) {
                $s = 0;
                $result .= $m[1];
            }
            break;
        }
    }
    return $result;
}

function parse_result($programmingid, $url, $max = 0, $lowest = 0) {
    global $CFG, $DB, $moss_url;
    global $curl;
    
    // delete old moss result
    $DB->delete_records('programming_resemble', array('programmingid' => $programmingid));

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
    if (isset($CFG->proxyhost) && isset($CFG->proxyport) && ($CFG->proxyport > 0)) {
        curl_setopt($curl, CURLOPT_PROXY, $CFG->proxyhost);
        curl_setopt($curl, CURLOPT_PROXYPORT, $CFG->proxyport);
        if (isset($CFG->proxyuser) && isset($CFG->proxypass)) {
            curl_setopt($curl, CURLOPT_PROXYUSERPWD, $CFG->proxyuser.':'.$CFG->proxypass);
        }
    }

    parse_index($programmingid, $url, $max, $lowest);

    curl_close($curl);
}

function fetch_by_curl($url) {
    global $CFG;
    global $curl;

    echo "Fetching $url\n"; flush();
    curl_setopt($curl, CURLOPT_URL, $url);
    $ret = curl_exec($curl);

    return explode("\n", $ret);
}

?>
