<?PHP

/////////////////////////////////////////////////////////////////////////////////
///  Code fragment to define the version of NEWMODULE
///  This fragment is called by moodle_needs_upgrading() and /admin/index.php
/////////////////////////////////////////////////////////////////////////////////

defined('MOODLE_INTERNAL') || die();

$plugin->version    = 2012122601;               // The current module version (Date: YYYYMMDDXX)
$plugin->requires   = 2011062402;               // Requires this Moodle version--2011062402 is the latest version fits for moodle1.9x
$plugin->component  = 'mod_programming';        // Full name of the plugin (used for diagnostics)
$plugin->release    = '2.x (Build: 2012051101)';// Human-readable version name
$plugin->cron       = 0;                        // Period for cron to check this module (secs)

?>
