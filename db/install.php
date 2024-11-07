<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file replaces the legacy STATEMENTS section in db/install.xml,
 * lib.php/modulename_install() post installation hook and partially defaults.php
 *
 * @package    mod
 * @subpackage programming
 * @copyright  2011 Your Name <your@email.adress>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Post installation procedure
 *
 * @see upgrade_plugins_modules()
 */
function xmldb_programming_install() {
        global $DB;
     $sql="insert into {programming_languages} (name, description, sourceext, headerext) VALUES ('gcc-3.3', 'C (GCC 3.3)', '.c', '.h')";       
     $DB->execute($sql);
     $sql="insert into {programming_languages} (name, description, sourceext, headerext) VALUES ('g++-3.3', 'C++ (G++ 3.3)', '.cpp .cxx', '.h .hpp')";       
     $DB->execute($sql);
     $sql="insert into {programming_languages} (name, description, sourceext, headerext) VALUES ('java-1.5', 'Java (Sun JDK 5)', '.java', NULL)";       
     $DB->execute($sql);
     $sql="insert into {programming_languages} (name, description, sourceext, headerext) VALUES ('java-1.6', 'Java (Sun JDK 6)', '.java', NULL)";       
     $DB->execute($sql);
     $sql="insert into {programming_languages} (name, description, sourceext, headerext) VALUES ('fpc-2.2', 'Pascal (Free Pascal 2)', '.pas', NULL)";       
     $DB->execute($sql);
     $sql="insert into {programming_languages} (name, description, sourceext, headerext) VALUES ('python-2.5', 'Python 2.5', '.py', NULL)";       
     $DB->execute($sql);
     $sql="insert into {programming_languages} (name, description, sourceext, headerext) VALUES ('gmcs-2.0', 'C# (Mono 2.0)', '.cs', NULL)";       
     $DB->execute($sql);
     $sql="insert into {programming_languages} (name, description, sourceext, headerext) VALUES ('bash-3', 'Bash (Bash 3)', '.sh', NULL)";       
     $DB->execute($sql);
    $DB->set_field('modules', 'visible', 0, array('name'=>'programming'));
}

/**
 * Post installation recovery procedure
 *
 * @see upgrade_plugins_modules()
 */
function xmldb_programming_install_recovery() {
}
