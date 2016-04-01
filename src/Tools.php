<?php

/**
 * Collection of helpful static methods for development
 */
class Dev_Tools {



    /**
     * Prints an array structurally.
     * @param array $array <p>
     * The array to be print
     * </p>
     * @param bool $exit <p>
     * Optional: Choose if after printing exit from code or not ( Default: true )
     * </p>
     * @param bool $in_comment <p>
     * Optional: To hide the debug into an HTML comment ( Default: false )
     * </p>
     * @author Davide Caruso
     */
    public static function p( $array, $exit = true, $in_comment = false ) {

        # Retrieves the names of the passed arguments
        $file = $line = '';
        $bt = debug_backtrace();
        extract( array_pop( $bt ) );
        preg_match( '/\bp\s*\(\s*(\S*?)\s*\)/i', implode( '', array_slice( file( $file ), $line - 1 ) ), $matches );
        $label = @$matches[1];

        echo ( $in_comment ? '<!-- ' : '' ),

        "<strong>{$label}</strong>",

        '<pre>',

        ( is_array( $array ) || is_object( $array ) ? print_r( $array, true ) : var_dump( $array, true ) ),

        '</pre><br />',

        ( $in_comment ? ' -->' : '' );

        if( $exit ) exit();

    }



    /**
     * Redirects to an URL with PHP or, eventually, with JavaScript
     * @param string $url <p>
     * The url of the location.
     * </p>
     * @return void
     * @author Davide Caruso
     */
    public static function redirect( $url ) {
        if ( !headers_sent() ) {
            header( 'Location: ' . $url );
            exit;
        } else {
            echo <<<RDR
                <script type="text/javascript">
                    window.location.href="{$url}";
                </script>
                <noscript>
                    <meta http-equiv="refresh" content="0;url='{$url}"/>
                </noscript>
RDR;
            exit;
        }
    }



    /**
     * Inside a function, prints the values of the passed arguments
     * @param string $get_args <p>
     * The list of the arguments ( @function <u>func_get_args()</u> )
     * </p>
     * @param string $num_args <p>
     * The number of the arguments ( @function <u>func_num_args()</u> )
     * </p>
     * @return string
     * @author Davide Caruso
     */
    public static function print_passed_args( $get_args = '', $num_args = '' ) {

        if( $num_args )
            echo "Number of arguments: {$num_args}<br />\n";

        if( $get_args ) {

            $num_args = $num_args ? $num_args : count( $get_args );

            for ( $i = 0; $i < $num_args; $i++ )
                echo "Argument {$i} is: {$get_args[$i]}<br />\n";

        }

        exit();

    }



    /**
     * Check if is a valid email
     * @param string $email <p>
     * The email to check
     * </p>
     * @return bool Returns true when is valid email.
     * @author Davide Caruso
     */
    public static function validate_email( $email ) {
    //    return ( bool ) preg_match( '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/', filter_var( filter_var( trim( $email ), FILTER_SANITIZE_EMAIL ), FILTER_VALIDATE_EMAIL ) );
        return ( bool ) filter_var( filter_var( $email, FILTER_SANITIZE_EMAIL ), FILTER_VALIDATE_EMAIL );
    }



    /**
     * Remove backslashes recursively
     * @param array $array <p>
     * The array which contains the string to be stripped
     * </p>
     * @return array|string Backslashes stripped
     * @author Davide Caruso
     */
    public static function stripslashes_array( $array ) {
        return is_array( $array ) ? array_map( 'stripslashes_array', $array ) : stripslashes( $array );
    }



    /**
     * Quote recursively array's strings with slashes
     * @param array $array <p>
     * The array which contain the strings to be escaped.
     * </p>
     * @return array|string The escaped array
     * @author Davide Caruso
     */
    public static function addslashes_array( $data ) {
        return is_array( $data ) ? array_map( 'addslashes', $data ) : addslashes( $data );
    }



    /**
     * Sort an array by a specific key value
     * @param array $array <p>
     * The array to be sorted
     * </p>
     * @param string $field <p>
     * The sort order field ( the key of the array )
     * </p>
     * @param const|int $order <p>
     * The order direction value
     * </p>
     * @return void
     * @author Davide Caruso
     */
    public static function sort_array( &$array, $field, $order = SORT_ASC ) {
        usort( $array, create_function( '$a, $b', '
                            $a = $a["' . $field . '"];
                            $b = $b["' . $field . '"];
                            if ( $a == $b ) return 0;
                            return ( $a ' . ( $order == SORT_DESC ? '>' : '<' ) .' $b ) ? -1 : 1;
            ' ) );
    }



    /**
     * Convert coordinate from DMS format to decimal
     * @param string $dms <p>
     * The DMS coordinate
     * </p>
     * @return bool|string Returns the coordinate in decimal format
     * @author Davide Caruso
     */
    public static function dms_to_decimal( $dms = "37°51'26,4''N" ) {

        $split = preg_split( "/[°',]+/", $dms );

        $degrees    = $split[0];
        $minutes    = $split[1];
        $seconds    = $split[2];
        $direction  = strtolower( $split[count( $split ) - 1] );

        $direction_options = array( 'n', 's', 'e', 'w' );

        # Degrees must be integer between 0 and 180
        if( !is_numeric( $degrees ) || $degrees < 0 || $degrees > 180 )
            $decimal = false;

        # Minutes must be integer or float between 0 and 59
        elseif( !is_numeric( $minutes ) || $minutes < 0 || $minutes > 59 )
            $decimal = false;

        # Seconds must be integer or float between 0 and 59
        elseif( !is_numeric( $seconds ) || $seconds < 0 || $seconds > 59 )
            $decimal = false;

        elseif( !in_array( $direction, $direction_options ) )
            $decimal = false;

        else {

            $decimal = $degrees + ( $minutes / 60 ) + ( $seconds / 3600 );

            # Reverse for south or west coordinates; north is assumed
            if( $direction == 's' || $direction == 'w' )
                $decimal *= -1;
        }

        return $decimal;
    }



    /**
     * Converts a seconds interval to days, hours, minutes and seconds
     * @param int|string $seconds <p>
     * The seconds
     * </p>
     * @return string
     * @author Davide Caruso
     */
    public static function seconds_to_time ( $seconds ) {
        $dtF = new DateTime( "@0" );
        $dtT = new DateTime( "@$seconds" );
        return $dtF->diff( $dtT )->format( '%a days, %h hours, %i minutes and %s seconds' );
    }



    /**
     * Un-escape a string
     * @param string $string <p>
     * The string that will be un-escaped
     * </p>
     * @return string The un-escaped string
     * @author Davide Caruso
     */
    public static function unescape( $string ) {

        $search = array( "\\x00", "\\n", "\\r", "\\\x1a" );
        $replace = array( "\x00","\n", "\r", "\x1a" );

        $unescaped_string = str_replace( $search, $replace, $string );

        $search = array( "\'", '\\'.'"' );
        $replace = array(  "'", '"', );

        $unescaped_string = str_replace( $search, $replace, $unescaped_string );

        $search = array( "\\\\" );
        $replace = array( "\\" );

        $unescaped_string = str_replace( $search, $replace, $unescaped_string );

        return $unescaped_string;

    }



    /**
     * Returns the formatted size from a bytes value
     * @param int $bytes <p>
     * The value in bytes
     * </p>
     * @return string The formatted size
     * @author Davide Caruso
     */
    public static function get_formatted_size( $bytes ) {

        switch ( true ) {

            case $bytes >= 1073741824:
                return number_format( $bytes / 1073741824, 2 ) . ' GB';

            case $bytes >= 1048576:
                return number_format( $bytes / 1048576, 2 ) . ' MB';

            case $bytes >= 1024:
                return number_format( $bytes / 1024, 2 ) . ' KB';

            case $bytes > 1:
                return $bytes . ' bytes';

            case $bytes == 1:
                return $bytes . ' byte';

            default:
                return '0 bytes';

        }
    }



    /**
     * Returns a list of days during a time interval
     * @param $start_date <p>
     * The start date of the interval
     * </p>
     * @param $end_date <p>
     * The end date of the interval
     * </p>
     * @return array The list of days
     */
    public static function get_data_range( $start_date, $end_date ) {

        $data_range = array();

        $date_from = mktime( 1, 0, 0, substr( $start_date, 5, 2 ), substr( $start_date, 8, 2 ), substr( $start_date, 0, 4 ) );
        $date_to = mktime( 1, 0, 0, substr( $end_date, 5, 2 ), substr( $end_date, 8, 2 ), substr( $end_date, 0, 4 ) );

        if( $date_to >= $date_from ) {

            array_push( $data_range, date( 'Y-m-d', $date_from ) );

            while ( $date_from < $date_to ) {
                $date_from += 86400;
                array_push( $data_range, date( 'Y-m-d', $date_from ) );
            }

        }

        return $data_range;
    }



}