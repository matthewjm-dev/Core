<?php // IPS-CORE Functions

class ipsCore_functions {

	public function generate_slug( $name ) {
        return strtolower( str_replace( ' ', '-', preg_replace('/[^A-Za-z0-9\-]/', '', $name) ) );
	}

    public function generate_dbslug( $name ) {
        return strtolower( str_replace( ' ', '_', preg_replace('/[^A-Za-z0-9\_]/', '', $name) ) );
    }

	public function redirect( $url ) {
		$path = ipsCore::$site_base . $url;
		header( "Location: " . $path );
	}

	public function is_page( $page ) {
		$cur = ipsCore::$router->get_route()->get_controller();
		if ( $cur == $page ) {
			return true;
		} return false;
	}

    public function format_bytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        // $bytes /= pow(1024, $pow); OR
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    public function random_string($length = 64, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'): string {
        if ($length < 1) {
            //throw new \RangeException("Length must be a positive integer");
            ipsCore::add_error('Length must be a positive integer', true);
        }
        $pieces = [];
        $max = mb_strlen($keyspace, '8bit') - 1;
        for ($i = 0; $i < $length; ++$i) {
            $pieces []= $keyspace[random_int(0, $max)];
        }
        return implode('', $pieces);
    }
}
