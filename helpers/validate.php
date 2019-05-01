<?php // IPS-CORE Validation Functions

class ipsCore_validate {
	public static function link( $url ) {
		$start = substr( $url, 0, 4 );

		if ( !in_array( $start, [ 'http', 'www.' ] ) ) {
			if ( substr( $start, 0, 1 ) != '/' ) {
				return '/' . $url;
			}
		}
		return $url;
	}

	public static function stripSlug( $url ) {
		return trim( rtrim( $url, '/' ), '/' );
	}

	public static function notEmpty( $check ) {
		if ( is_array( $check ) ) {
			extract(self::_defaults($check));
		}

		if ( empty( $check ) && $check != '0' ) {
			return false;
		}
		return self::_check( $check, '/[^\s]+/m' );
	}

	public static function email( $check ) {
		return ( filter_var( $check, FILTER_VALIDATE_EMAIL ) !== false );
	}

	public static function inList( $check, $list, $strict = true ) {
		return in_array( $check, $list, $strict );
	}

	public static function minLength( $check, $min ) {
		return mb_strlen( $check ) >= $min;
	}

	public static function maxLength( $check, $max ) {
		return mb_strlen( $check ) <= $max;
	}

	public static function naturalNumber( $check, $allowZero = false ) {
		$regex = $allowZero ? '/^(?:0|[1-9][0-9]*)$/' : '/^[1-9][0-9]*$/';
		return self::_check( $check, $regex );
	}

	public static function numeric( $check ) {
		return is_numeric( $check );
	}

	public static function range( $check, $lower = null, $upper = null ) {
		if ( ! is_numeric( $check ) ) {
			return false;
		}
		if ( isset( $lower ) && isset( $upper ) ) {
			return ( $check > $lower && $check < $upper );
		}
		return is_finite($check);
	}

	public static function alphaNumeric( $check ) {
		if ( is_array( $check ) ) {
			extract( self::_defaults( $check ) );
		}

		if ( empty( $check ) && $check != '0' ) {
			return false;
		}
		return self::_check( $check, '/^[\p{Ll}\p{Lm}\p{Lo}\p{Lt}\p{Lu}\p{Nd}]+$/Du' );
	}

	public static function date( $check, $format = 'ymd', $regex = null ) {
		if ($regex !== null) {
			return self::_check( $check, $regex );
		}

		$regex['dmy'] = '%^(?:(?:31(\\/|-|\\.|\\x20)(?:0?[13578]|1[02]))\\1|(?:(?:29|30)(\\/|-|\\.|\\x20)(?:0?[1,3-9]|1[0-2])\\2))(?:(?:1[6-9]|[2-9]\\d)?\\d{2})$|^(?:29(\\/|-|\\.|\\x20)0?2\\3(?:(?:(?:1[6-9]|[2-9]\\d)?(?:0[48]|[2468][048]|[13579][26])|(?:(?:16|[2468][048]|[3579][26])00))))$|^(?:0?[1-9]|1\\d|2[0-8])(\\/|-|\\.|\\x20)(?:(?:0?[1-9])|(?:1[0-2]))\\4(?:(?:1[6-9]|[2-9]\\d)?\\d{2})$%';
		$regex['mdy'] = '%^(?:(?:(?:0?[13578]|1[02])(\\/|-|\\.|\\x20)31)\\1|(?:(?:0?[13-9]|1[0-2])(\\/|-|\\.|\\x20)(?:29|30)\\2))(?:(?:1[6-9]|[2-9]\\d)?\\d{2})$|^(?:0?2(\\/|-|\\.|\\x20)29\\3(?:(?:(?:1[6-9]|[2-9]\\d)?(?:0[48]|[2468][048]|[13579][26])|(?:(?:16|[2468][048]|[3579][26])00))))$|^(?:(?:0?[1-9])|(?:1[0-2]))(\\/|-|\\.|\\x20)(?:0?[1-9]|1\\d|2[0-8])\\4(?:(?:1[6-9]|[2-9]\\d)?\\d{2})$%';
		$regex['ymd'] = '%^(?:(?:(?:(?:(?:1[6-9]|[2-9]\\d)?(?:0[48]|[2468][048]|[13579][26])|(?:(?:16|[2468][048]|[3579][26])00)))(\\/|-|\\.|\\x20)(?:0?2\\1(?:29)))|(?:(?:(?:1[6-9]|[2-9]\\d)?\\d{2})(\\/|-|\\.|\\x20)(?:(?:(?:0?[13578]|1[02])\\2(?:31))|(?:(?:0?[1,3-9]|1[0-2])\\2(29|30))|(?:(?:0?[1-9])|(?:1[0-2]))\\2(?:0?[1-9]|1\\d|2[0-8]))))$%';
		$regex['dMy'] = '/^((31(?!\\ (Feb(ruary)?|Apr(il)?|June?|(Sep(?=\\b|t)t?|Nov)(ember)?)))|((30|29)(?!\\ Feb(ruary)?))|(29(?=\\ Feb(ruary)?\\ (((1[6-9]|[2-9]\\d)(0[48]|[2468][048]|[13579][26])|((16|[2468][048]|[3579][26])00)))))|(0?[1-9])|1\\d|2[0-8])\\ (Jan(uary)?|Feb(ruary)?|Ma(r(ch)?|y)|Apr(il)?|Ju((ly?)|(ne?))|Aug(ust)?|Oct(ober)?|(Sep(?=\\b|t)t?|Nov|Dec)(ember)?)\\ ((1[6-9]|[2-9]\\d)\\d{2})$/';
		$regex['Mdy'] = '/^(?:(((Jan(uary)?|Ma(r(ch)?|y)|Jul(y)?|Aug(ust)?|Oct(ober)?|Dec(ember)?)\\ 31)|((Jan(uary)?|Ma(r(ch)?|y)|Apr(il)?|Ju((ly?)|(ne?))|Aug(ust)?|Oct(ober)?|(Sep)(tember)?|(Nov|Dec)(ember)?)\\ (0?[1-9]|([12]\\d)|30))|(Feb(ruary)?\\ (0?[1-9]|1\\d|2[0-8]|(29(?=,?\\ ((1[6-9]|[2-9]\\d)(0[48]|[2468][048]|[13579][26])|((16|[2468][048]|[3579][26])00)))))))\\,?\\ ((1[6-9]|[2-9]\\d)\\d{2}))$/';
		$regex['My'] = '%^(Jan(uary)?|Feb(ruary)?|Ma(r(ch)?|y)|Apr(il)?|Ju((ly?)|(ne?))|Aug(ust)?|Oct(ober)?|(Sep(?=\\b|t)t?|Nov|Dec)(ember)?)[ /]((1[6-9]|[2-9]\\d)\\d{2})$%';
		$regex['my'] = '%^((0[123456789]|10|11|12)([- /.])(([1][9][0-9][0-9])|([2][0-9][0-9][0-9])))$%';
		$regex['ym'] = '%^((([1][9][0-9][0-9])|([2][0-9][0-9][0-9]))([- /.])(0[123456789]|10|11|12))$%';
		$regex['y'] = '%^(([1][9][0-9][0-9])|([2][0-9][0-9][0-9]))$%';

		$format = ( is_array( $format ) ) ? array_values( $format ) : array( $format );
		foreach ( $format as $key ) {
			if ( self::_check( $check, $regex[ $key ] ) === true ) {
				return true;
			}
		}
		return false;
	}

	public static function boolean( $check ) {
		$booleanList = array( 0, 1, '0', '1', true, false );
		return in_array( $check, $booleanList, true );
	}

	public static function decimal( $check, $places = null, $regex = null ) {
		if ( $regex === null ) {
			$lnum = '[0-9]+';
			$dnum = "[0-9]*[\.]{$lnum}";
			$sign = '[+-]?';
			$exp = "(?:[eE]{$sign}{$lnum})?";

			if ( $places === null ) {
				$regex = "/^{$sign}(?:{$lnum}|{$dnum}){$exp}$/";

			} elseif ( $places === true ) {
				if (is_float($check) && floor($check) === $check) {
					$check = sprintf("%.1f", $check);
				}
				$regex = "/^{$sign}{$dnum}{$exp}$/";

			} elseif ( is_numeric( $places ) ) {
				$places = '[0-9]{' . $places . '}';
				$dnum = "(?:[0-9]*[\.]{$places}|{$lnum}[\.]{$places})";
				$regex = "/^{$sign}{$dnum}{$exp}$/";
			}
		}
		return self::_check( $check, $regex );
	}

	public static function equalTo( $check, $comparedTo ) {
		return ( $check === $comparedTo );
	}

	public static function isTime( $check ) {
		return self::_check( $check, '%^((0?[1-9]|1[012])(:[0-5]\d){0,2} ?([AP]M|[ap]m))$|^([01]\d|2[0-3])(:[0-5]\d){0,2}$%' );
	}

	public static function telephone( $check ) {
		return self::_check( $check, '%^(\(?\+?[0-9]{0,2}\)?)?[0-9_\- \(\)\/ext\.]{7,20}$%' );
	}

	public static function postcodeGB( $check ) {
		return self::_check( $check, '%^([Gg][Ii][Rr]\s?0[Aa]{2})|((([A-Za-z][0-9]{1,2})|(([A-Za-z][A-Ha-hJ-Yj-y][0-9]{1,2})|(([AZa-z][0-9][A-Za-z])|([A-Za-z][A-Ha-hJ-Yj-y][0-9]?[A-Za-z]))))\s?[0-9][A-Za-z]{2})$%i');
	}
}
