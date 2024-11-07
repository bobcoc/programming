<?php

// This file keeps track of upgrades to 
// the programming module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

function xmldb_programming_upgrade($oldversion=0) {

    global $CFG, $THEME, $DB;

    $result = true;
    $dbman = $DB->get_manager();
/// And upgrade begins here. For each one, you'll need one 
/// block of code similar to the next one. Please, delete 
/// this comment lines once this file start handling proper
/// upgrade code.

/// if ($result && $oldversion < YYYYMMDD00) { //New version in version.php
///     $result = result of "/lib/ddllib.php" function calls
/// }
//2012051101
    //2011062402 is the latest version fits for moodle1.9x
    if ($result && $oldversion < 2011062402) {
        $prefix = $DB->get_prefix();
        $sql = "ALTER TABLE `{$prefix}programming` CHANGE  COLUMN  `description` `intro` longtext NOT NULL,";
        $sql .= "CHANGE COLUMN `descformat` `introformat`  tinyint(2) NOT NULL DEFAULT 0;";
        $DB->change_database_structure($sql);
        upgrade_mod_savepoint(true, 2011062402, 'programming');
        
    }

    if ($result && $oldversion < 2012081804) {
        $prefix = $DB->get_prefix();
        $sql = "ALTER TABLE {$prefix}programming_presetcode CHANGE  COLUMN presetcodeforcheck presetcodeforcheck LONGTEXT NULL;";
        $DB->change_database_structure($sql);
        upgrade_mod_savepoint(true, 2012081804, 'programming');
    }
//2012080803
    if ($result && $oldversion < 2012081903) {
        $prefix = $DB->get_prefix();
        $sql = "ALTER TABLE {$prefix}programming_datafile CHANGE COLUMN checkdatasize checkdatasize bigint(10) NULL, CHANGE COLUMN checkdata checkdata longblob NULL;";
        $DB->change_database_structure($sql);
        upgrade_mod_savepoint(true, 2012081903, 'programming');
    }
    //2012122601
    if ($result && $oldversion < 2012122601) {
                $table = new xmldb_table('programming_languages');
        $field = new xmldb_field('langmode', XMLDB_TYPE_CHAR, '50', null,
                                 null, null, null, 'headerext');

        //  add field langmode for programming_languages .
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            // Set the cutoffdate to the duedate if preventlatesubmissions was enabled.
            $sql = "UPDATE {programming_languages} SET langmode = 'text/x-csrc' WHERE name = 'gcc-3.3'";
            $DB->execute($sql);
            $sql = "UPDATE {programming_languages} SET langmode = 'text/x-c++src' WHERE name = 'g++-3.3'";
            $DB->execute($sql);
            $sql = "UPDATE {programming_languages} SET langmode = 'text/x-java' WHERE name = 'java-1.5'";
            $DB->execute($sql);
            $sql = "UPDATE {programming_languages} SET langmode = 'text/x-java' WHERE name = 'java-1.6'";
            $DB->execute($sql);
            $sql = "UPDATE {programming_languages} SET langmode = 'text/x-pascal' WHERE name = 'fpc-2.2'";
            $DB->execute($sql);
            $sql = "UPDATE {programming_languages} SET langmode = 'text/x-python' WHERE name = 'python-2.5'";
            $DB->execute($sql);
            $sql = "UPDATE {programming_languages} SET langmode = 'text/x-csharp' WHERE name = 'gmcs-2.0'";
            $DB->execute($sql);
            $sql = "UPDATE {programming_languages} SET langmode = 'text/x-sh' WHERE name = 'bash-3'";
            $DB->execute($sql);
        }
       upgrade_mod_savepoint(true, 2012122601, 'programming');
    }    


    return $result;
}

?>
