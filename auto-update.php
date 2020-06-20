<?php
/**
 * AUTO UPDATE FOR WP-CLI
 *
 * Enables auto updating of the WP-CLI client
 *  - Checks every 12 hours
 *  - Doesn't check prior to wp cli {command}
 *  - Can be bypassed with --disable-autoupdate
 *
 * @version 1.0.0
 */

/**
 * Load the Utils library
 */
use \WP_CLI\Utils;

/**
* Bail if not a WP-CLI request
*/
if ( ! defined( 'WP_CLI' ) ) {
   return;
}
/**
 * Run check before running a command
 */
WP_CLI::add_hook('before_run_command', function(){
  /*
   * This is a tad hacky as we are not yet in a command, so have to guess, bail if no or just one argument is present i.e wp help
   * Where extra config might be Commmand | SubCommand
   */
  if( !empty( WP_CLI::get_runner()->arguments ) && isset( WP_CLI::get_runner()->arguments[1] ) ){
    $extra_config = WP_CLI::get_runner()->extra_config[
                      WP_CLI::get_runner()->arguments[0].' '.WP_CLI::get_runner()->arguments[1]
                    ];
  }else{
    return;
  }

  /*
   * Test conditions:
   * If its wp cli [command]
   * If you append --disable-autoupdate
   * If you add disable-autoupdate: true within wp-cli.yml for a given command
   */
  if('cli' == WP_CLI::get_runner()->arguments[0] ||
     null !== Utils\get_flag_value( WP_CLI::get_runner()->assoc_args , 'disable-autoupdate' ) ||
     isset( $extra_config['disable-autoupdate'] )
  ) return;

  /*
   * This makes a MASSIVE assumption that .wp-cli/ exists in the home folder
   * So um sorry windows folks, however it seems an assumption other parts of WP-CLI makes
   */
  $home = Utils\get_home_dir();

  try{
    $last_check = trim( @file_get_contents( $home . '/.wp-cli/LASTCHECK' ) );
  } catch ( Exception $e ){
    $last_check = 1;
  }

  if( !is_numeric( $last_check ) || ( $last_check < time() - 3600*12 ) )
  {
    WP_CLI::line('=======================================================');
    WP_CLI::line('Automatically checking for the latest version of WP-CLI');
    /*
     * Run wp-cli update command, this will also check for latest version
     * Return an object rather then putting to stdout
     * Run as a separate process and continue even if exit errors.
     */
    $update = WP_CLI::runcommand( 'cli update', [
      'return' => 'all',
      'launch' => true,
      'exit_error' => false,
    ] );
    /*
     * stderr can exist, while the process ran successfully for example PHP warnings
     * Return Code 0 is success and well anything else is borked
     */
    if (!empty( $update->stderr && 0 !=$update->return_code) ) {
         WP_CLI::warning( ' ❌ '.substr( $update->stderr, 9 ) );
    }else{
      /*
       * Using Substr as the output might include things we really don't care about
       */
      $msg = substr( $update->stdout, strpos($update->stdout, 'Success:' ) +9 );
      if( isset( $msg ) && 1 < strlen( $msg ) ){

        WP_CLI::line(' ✅ '.$msg);
      }
    }
    WP_CLI::line('=======================================================');
  }

  file_put_contents( $home . '/.wp-cli/LASTCHECK', time() );
  return;
} );
