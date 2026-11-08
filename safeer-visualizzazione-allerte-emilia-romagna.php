<?php
/**
 * Plugin Name:       SafeER Visualizzazione Allerte Emilia Romagna
 * Description:       Mostra il documento di allertamento pubblicato dalla Regione Emilia-Romagna (formato CAP) per la zona di allerta del Comune. Shortcode [allerta_meteo] e widget, grafica e parametri personalizzabili.
 * Version:           2.3.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Maurizio Malagoli
 * Author URI:        https://profiles.wordpress.org/malamaur/
 * License:           GPL-2.0-or-later
 * Text Domain:       safeer-visualizzazione-allerte-emilia-romagna
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Allerta_Meteo_ER {

	const VERSION  = '2.3.0';
	const ENDPOINT = 'https://allertameteo.regione.emilia-romagna.it/o/xmlcap';
	const PORTAL   = 'https://allertameteo.regione.emilia-romagna.it/';
	const CAP_NS   = 'urn:oasis:names:tc:emergency:cap:1.2';
	const OPTION   = 'amer_settings';
	const TZ       = 'Europe/Rome';

	/* ------------------------------------------------------------------ *
	 * Configurazione
	 * ------------------------------------------------------------------ */

	public static function zones() {
		return array(
			'A1' => 'Montagna romagnola (FC, RN)',
			'A2' => 'Alta collina romagnola (RA, FC, RN)',
			'B1' => 'Bassa collina e pianura romagnola (RA, FC, RN)',
			'B2' => 'Costa romagnola (RA, FC, RN)',
			'C1' => 'Montagna bolognese (BO)',
			'C2' => 'Collina bolognese (BO, RA)',
			'D1' => 'Pianura bolognese (BO, FE, RA)',
			'D2' => 'Costa ferrarese (FE)',
			'D3' => 'Pianura ferrarese (FE)',
			'E1' => 'Montagna emiliana centrale (PR, RE, MO)',
			'E2' => 'Collina emiliana centrale (PR, RE, MO)',
			'F1' => 'Pianura modenese (RE, MO)',
			'F2' => 'Pianura reggiana (RE)',
			'F3' => 'Pianura reggiana di Po (PR, RE)',
			'G1' => 'Montagna piacentino-parmense (PC, PR)',
			'G2' => 'Alta collina piacentino-parmense (PC, PR)',
			'H1' => 'Bassa collina piacentino-parmense (PC, PR)',
			'H2' => 'Pianura piacentino-parmense (PC, PR)',
		);
	}

	/** Fenomeni nell'ordine di visualizzazione => etichetta predefinita. */
	public static function phenomena() {
		return array(
			'IDRAULICO'                 => 'Idraulico',
			'IDROGEOLOGICO'             => 'Idrogeologico',
			'TEMPORALI'                 => 'Temporali',
			'VENTO'                     => 'Vento',
			'NEVE'                      => 'Neve',
			'TEMPERATURE_ESTREME'       => 'Temperature estreme',
			'GHIACCIO_PIOGGIA_CHE_GELA' => 'Pioggia che gela',
			'STATO_MARE'                => 'Stato del mare',
			'MAREGGIATE'                => 'Mareggiate',
		);
	}

	public static function defaults() {
		$d = array(
			// Generale.
			'zona'          => 'F1',
			'comune'        => '',
			'titolo'        => 'Allerte meteo',
			'cache'         => 30,
			// Logo.
			'logo'          => '',
			'logo_h'        => 30,
			// Aspetto.
			'sfondo'        => '#2a3036',
			'sfondo_img'    => '',
			'sfondo_overlay'=> 0,
			'testo'         => '#ffffff',
			'tabella'       => '#212529',
			'bordo'         => '#555e66',
			'chip'          => '#343a40',
			'onde'          => '#6c6c6c',
			'font'          => 0,
			'larghezza'     => 1140,
			// Visualizzazione.
			'domani'        => 1,
			'mostra_campana'=> 1,
			'onde_livello'  => 0,
			'mostra_info'   => 1,
			'mostra_emiss'  => 1,
			'mostra_zona'   => 1,
			'mostra_legenda'=> 1,
			'mostra_link'   => 0,
			'nascondi_na'   => 1,
			'livello_su'    => 'oggi',
			'mostra_giorno' => 1,
			'testo_nd'      => 'N.D.',
			// Livelli.
			'lv_color_0'    => '#66cc00',
			'lv_color_1'    => '#ffdd00',
			'lv_color_2'    => '#ff9900',
			'lv_color_3'    => '#ff0000',
			'lv_label_0'    => 'VERDE',
			'lv_label_1'    => 'GIALLA',
			'lv_label_2'    => 'ARANCIONE',
			'lv_label_3'    => 'ROSSA',
			'lv_desc_0'     => 'Situazione ordinaria',
			'lv_desc_1'     => 'Criticità ordinaria',
			'lv_desc_2'     => 'Criticità moderata',
			'lv_desc_3'     => 'Criticità elevata',
		);
		foreach ( self::phenomena() as $code => $label ) {
			$d[ 'fen_' . $code ]       = 1;
			$d[ 'fen_label_' . $code ] = $label;
		}
		return $d;
	}

	public static function settings() {
		return wp_parse_args( (array) get_option( self::OPTION, array() ), self::defaults() );
	}

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );
		add_action( 'admin_post_amer_flush', array( __CLASS__, 'flush_cache_action' ) );
		add_action( 'widgets_init', function () {
			register_widget( 'Allerta_Meteo_ER_Widget' );
		} );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( $links ) {
			array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=safeer' ) ) . '">Impostazioni</a>' );
			return $links;
		} );
	}

	public static function register() {
		wp_register_style( 'allerta-meteo-er', plugins_url( 'assets/allerta-meteo-er.css', __FILE__ ), array(), self::VERSION );
		wp_register_style( 'allerta-meteo-er-font', 'https://fonts.googleapis.com/css2?family=Titillium+Web:wght@300;400;600;700&display=swap', array(), self::VERSION );
		wp_register_script( 'allerta-meteo-er', plugins_url( 'assets/allerta-meteo-er.js', __FILE__ ), array(), self::VERSION, true );
		add_shortcode( 'allerta_meteo', array( __CLASS__, 'shortcode' ) );
	}

	/* ------------------------------------------------------------------ *
	 * Dati
	 * ------------------------------------------------------------------ */

	/**
	 * Scarica e interpreta il documento CAP.
	 *
	 * @param DateTimeInterface|null $when Istante di validità; null = adesso.
	 * @return array|WP_Error
	 */
	public static function get_document( $when = null ) {
		$s    = self::settings();
		$url  = self::ENDPOINT;
		$salt = (int) get_option( 'amer_cache_salt', 1 );
		$key  = 'amer_doc_' . $salt . '_now';

		if ( $when instanceof DateTimeInterface ) {
			$url .= '?data=' . str_replace( ' ', '%20', $when->format( 'Y-m-d H:i' ) );
			$key  = 'amer_doc_' . $salt . '_' . $when->format( 'YmdHi' );
		}

		$cached = get_transient( $key );
		if ( false !== $cached ) {
			return $cached;
		}

		$response = wp_remote_get( $url, array(
			'timeout'    => 15,
			'user-agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url() . ' (SafeER)',
		) );

		if ( is_wp_error( $response ) ) {
			$doc = $response;
		} elseif ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			$doc = new WP_Error( 'amer_http', 'Il servizio regionale ha risposto con codice ' . wp_remote_retrieve_response_code( $response ) . '.' );
		} else {
			$doc = self::parse( wp_remote_retrieve_body( $response ) );
		}

		if ( is_wp_error( $doc ) ) {
			// Servizio irraggiungibile: per "adesso" riusa l'ultima copia valida.
			$backup = get_option( 'amer_last_good' );
			if ( null === $when && is_array( $backup ) ) {
				$backup['stale'] = true;
				return $backup;
			}
			set_transient( $key, $doc, 5 * MINUTE_IN_SECONDS );
			return $doc;
		}

		set_transient( $key, $doc, max( 5, (int) $s['cache'] ) * MINUTE_IN_SECONDS );
		if ( null === $when ) {
			update_option( 'amer_last_good', $doc, false );
		}
		return $doc;
	}

	/** Converte l'XML CAP in array. */
	public static function parse( $body ) {
		if ( '' === trim( (string) $body ) ) {
			return new WP_Error( 'amer_empty', 'Documento di allertamento vuoto.' );
		}

		$prev = libxml_use_internal_errors( true );
		$xml  = simplexml_load_string( $body, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA );
		libxml_clear_errors();
		libxml_use_internal_errors( $prev );

		if ( ! $xml ) {
			return new WP_Error( 'amer_xml', 'Impossibile leggere il documento CAP.' );
		}

		$alert = $xml->children( self::CAP_NS );
		$doc   = array(
			'identifier' => (string) $alert->identifier,
			'sent'       => self::to_timestamp( (string) $alert->sent ),
			'note'       => trim( (string) $alert->note ),
			'zones'      => array(),
		);

		foreach ( $alert->info as $info ) {
			$params = array();
			foreach ( $info->parameter as $p ) {
				$params[ strtoupper( trim( (string) $p->valueName ) ) ] = strtoupper( trim( (string) $p->value ) );
			}
			$area = isset( $info->area ) ? (string) $info->area->areaDesc : '';
			$code = strtoupper( trim( preg_replace( '/^.*-/', '', $area ) ) );
			if ( '' === $code ) {
				continue;
			}
			$doc['zones'][ $code ] = array(
				'event'       => strtoupper( (string) $info->event ),
				'headline'    => trim( (string) $info->headline ),
				'description' => trim( (string) $info->description ),
				'onset'       => self::to_timestamp( (string) $info->onset ),
				'expires'     => self::to_timestamp( (string) $info->expires ),
				'params'      => $params,
			);
		}

		if ( empty( $doc['zones'] ) ) {
			return new WP_Error( 'amer_nozones', 'Il documento non contiene zone di allerta.' );
		}
		return $doc;
	}

	/** "2026-10-27T00:00+01:00[Europe/Rome]" → timestamp. */
	private static function to_timestamp( $value ) {
		$value = trim( preg_replace( '/\[[^\]]*\]$/', '', (string) $value ) );
		if ( '' === $value ) {
			return 0;
		}
		try {
			return ( new DateTimeImmutable( $value ) )->getTimestamp();
		} catch ( Exception $e ) {
			return 0;
		}
	}

	/** 0 verde, 1 gialla, 2 arancione, 3 rossa, -1 non valutato. */
	public static function level( $value ) {
		$v = strtoupper( (string) $value );
		if ( '' === $v ) {
			return -1;
		}
		if ( 0 === strpos( $v, 'ROSS' ) ) {
			return 3;
		}
		if ( 0 === strpos( $v, 'ARANC' ) ) {
			return 2;
		}
		if ( 0 === strpos( $v, 'GIALL' ) ) {
			return 1;
		}
		return 0;
	}

	/* ------------------------------------------------------------------ *
	 * Front-end
	 * ------------------------------------------------------------------ */

	public static function shortcode( $atts ) {
		$s   = self::settings();
		$map = array(
			// attributo shortcode => chiave impostazioni
			'zona'      => 'zona',
			'comune'    => 'comune',
			'titolo'    => 'titolo',
			'logo'      => 'logo',
			'sfondo'    => 'sfondo',
			'testo'     => 'testo',
			'domani'    => 'domani',
			'campana'   => 'mostra_campana',
			'info'      => 'mostra_info',
			'emissione' => 'mostra_emiss',
			'giorno'    => 'mostra_giorno',
			'legenda'   => 'mostra_legenda',
			'link'      => 'mostra_link',
		);
		$atts = (array) $atts;
		foreach ( $map as $attr => $key ) {
			if ( ! isset( $atts[ $attr ] ) ) {
				continue;
			}
			$val = $atts[ $attr ];
			if ( is_int( self::defaults()[ $key ] ) ) {
				$s[ $key ] = in_array( strtolower( (string) $val ), array( '1', 'si', 'sì', 'yes', 'true' ), true ) ? 1 : 0;
			} elseif ( in_array( $key, array( 'sfondo', 'testo' ), true ) ) {
				$s[ $key ] = sanitize_hex_color( $val ) ?: $s[ $key ];
			} elseif ( 'logo' === $key ) {
				$s[ $key ] = esc_url_raw( $val );
			} else {
				$s[ $key ] = sanitize_text_field( $val );
			}
		}
		$s['zona'] = strtoupper( $s['zona'] );
		return self::render( $s );
	}

	/** Costruisce le colonne-giorno. */
	private static function columns( array $s ) {
		$tz   = new DateTimeZone( self::TZ );
		$now  = new DateTimeImmutable( 'now', $tz );
		$cols = array();

		$doc  = self::get_document();
		$zone = ( ! is_wp_error( $doc ) && isset( $doc['zones'][ $s['zona'] ] ) ) ? $doc['zones'][ $s['zona'] ] : null;
		$cols[] = array(
			'id'   => 'oggi',
			'date' => $zone && $zone['onset'] ? $zone['onset'] : $now->getTimestamp(),
			'doc'  => is_wp_error( $doc ) ? null : $doc,
			'zone' => $zone,
		);

		if ( $s['domani'] ) {
			$t    = $now->modify( '+1 day' )->setTime( 12, 0 );
			$doc2 = self::get_document( $t );
			$z2   = ( ! is_wp_error( $doc2 ) && isset( $doc2['zones'][ $s['zona'] ] ) ) ? $doc2['zones'][ $s['zona'] ] : null;
			// Se il bollettino di domani non è ancora uscito il servizio restituisce quello di oggi.
			if ( $z2 && wp_date( 'Y-m-d', $z2['onset'], $tz ) !== $t->format( 'Y-m-d' ) ) {
				$z2 = null;
			}
			$cols[] = array(
				'id'   => 'domani',
				'date' => $t->getTimestamp(),
				'doc'  => $z2 ? $doc2 : null,
				'zone' => $z2,
			);
		}
		return $cols;
	}

	public static function render( array $s ) {
		wp_enqueue_style( 'allerta-meteo-er' );
		wp_enqueue_script( 'allerta-meteo-er' );
		if ( $s['font'] ) {
			wp_enqueue_style( 'allerta-meteo-er-font' );
		}

		$zones = self::zones();
		if ( ! isset( $zones[ $s['zona'] ] ) ) {
			$s['zona'] = 'F1';
		}
		$tz   = new DateTimeZone( self::TZ );
		$uid  = 'amer-' . wp_unique_id();
		$cols = self::columns( $s );

		// Righe della tabella.
		$rows = array();
		foreach ( self::phenomena() as $code => $default_label ) {
			if ( empty( $s[ 'fen_' . $code ] ) ) {
				continue;
			}
			$cells = array();
			$any   = false;
			foreach ( $cols as $c ) {
				$lv      = $c['zone'] ? self::level( isset( $c['zone']['params'][ $code ] ) ? $c['zone']['params'][ $code ] : '' ) : null;
				$cells[] = $lv;
				if ( null !== $lv && -1 !== $lv ) {
					$any = true;
				}
			}
			if ( ! $any && $s['nascondi_na'] ) {
				continue; // es. stato del mare nelle zone interne.
			}
			$rows[] = array( 'label' => $s[ 'fen_label_' . $code ] ?: $default_label, 'cells' => $cells );
		}

		// Livello complessivo: su tutti i parametri della zona (anche quelli nascosti).
		$overall  = -1;
		$col_lv   = array();
		foreach ( $cols as $i => $c ) {
			if ( ! $c['zone'] || ( 'oggi' === $s['livello_su'] && $i > 0 ) ) {
				continue;
			}
			$lv = self::level( $c['zone']['event'] );
			foreach ( $c['zone']['params'] as $v ) {
				$lv = max( $lv, self::level( $v ) );
			}
			$col_lv[ $i ] = $lv;
			$overall      = max( $overall, $lv );
		}

		// Giornata (o giornate) a cui si riferisce il livello complessivo.
		$ref_days = array();
		foreach ( $col_lv as $i => $lv ) {
			if ( $lv === $overall ) {
				$ref_days[] = ( 'oggi' === $cols[ $i ]['id'] ? 'oggi' : 'domani' ) . ' ' . wp_date( 'd/m', $cols[ $i ]['date'], $tz );
			}
		}
		$ref_text = $ref_days ? implode( ' e ', $ref_days ) : '';

		$first   = $cols[0];
		$doc     = $first['doc'];
		$style   = self::style_vars( $s );
		$classes = 'amer' . ( $s['sfondo_img'] ? ' amer--img' : '' ) . ( $s['mostra_campana'] ? '' : ' amer--nobell' );

		ob_start();
		?>
		<section id="<?php echo esc_attr( $uid ); ?>" class="<?php echo esc_attr( $classes ); ?>" style="<?php echo esc_attr( $style ); ?>" aria-labelledby="<?php echo esc_attr( $uid ); ?>-t">
			<div class="amer__inner">
				<h2 class="amer__title" id="<?php echo esc_attr( $uid ); ?>-t">
					<?php if ( $s['logo'] ) : ?>
						<img class="amer__logo" src="<?php echo esc_url( $s['logo'] ); ?>" alt="<?php echo esc_attr( $s['comune'] ? 'Logo ' . $s['comune'] : '' ); ?>">
					<?php endif; ?>
					<span><?php echo esc_html( $s['titolo'] ); ?></span>
					<?php if ( $s['comune'] ) : ?><span class="amer__sr"> – <?php echo esc_html( $s['comune'] ); ?></span><?php endif; ?>
				</h2>

				<div class="amer__grid">
					<div class="amer__status">
						<?php if ( $s['mostra_campana'] ) : ?>
							<div class="amer__bell" aria-hidden="true"><?php echo self::bell_svg( $overall, $s ); // phpcs:ignore ?></div>
						<?php endif; ?>
						<p class="amer__level">
							<span class="amer__level-label">Livello di allerta:</span>
							<?php if ( $overall >= 0 ) : ?>
								<span class="amer__chip amer--l<?php echo (int) $overall; ?>"><?php echo esc_html( $s[ 'lv_label_' . $overall ] ); ?></span>
								<?php if ( $s[ 'lv_desc_' . $overall ] ) : ?>
									– <span class="amer__chip amer--l<?php echo (int) $overall; ?>"><?php echo esc_html( $s[ 'lv_desc_' . $overall ] ); ?></span>
								<?php endif; ?>
								<?php if ( $s['mostra_giorno'] && $ref_text ) : ?>
									<span class="amer__level-day">riferito a <?php echo esc_html( $ref_text ); ?></span>
								<?php endif; ?>
							<?php else : ?>
								<span class="amer__chip amer--nd">dato non disponibile</span>
							<?php endif; ?>
						</p>
					</div>

					<div class="amer__tablewrap">
						<table class="amer__table">
							<caption class="amer__sr">Livelli di allerta per fenomeno, zona <?php echo esc_html( $s['zona'] ); ?></caption>
							<thead>
								<tr>
									<th scope="col">Livello di allerta</th>
									<?php foreach ( $cols as $c ) : ?>
										<th scope="col"><?php echo esc_html( wp_date( 'd/m', $c['date'], $tz ) ); ?></th>
									<?php endforeach; ?>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $rows as $r ) : ?>
									<tr>
										<th scope="row"><?php echo esc_html( $r['label'] ); ?></th>
										<?php foreach ( $r['cells'] as $lv ) : ?>
											<?php if ( null === $lv || -1 === $lv ) : ?>
												<td class="amer__cell amer--nd"><?php echo esc_html( $s['testo_nd'] ); ?></td>
											<?php else : ?>
												<td class="amer__cell amer--l<?php echo (int) $lv; ?>"><?php echo esc_html( $s[ 'lv_label_' . $lv ] ); ?></td>
											<?php endif; ?>
										<?php endforeach; ?>
									</tr>
								<?php endforeach; ?>
								<?php if ( $s['mostra_info'] ) : ?>
									<tr class="amer__inforow">
										<td></td>
										<?php foreach ( $cols as $c ) : ?>
											<td>
												<?php if ( $c['zone'] ) : ?>
													<button type="button" class="amer__btn amer__btn--primary" data-amer-open="<?php echo esc_attr( $uid . '-' . $c['id'] ); ?>" aria-haspopup="dialog">Info<span class="amer__sr"> <?php echo esc_html( wp_date( 'd/m', $c['date'], $tz ) ); ?></span></button>
												<?php endif; ?>
											</td>
										<?php endforeach; ?>
									</tr>
								<?php endif; ?>
							</tbody>
						</table>
					</div>

					<div class="amer__side">
						<?php if ( $doc && $s['mostra_emiss'] ) : ?>
							<p class="amer__meta">
								<?php if ( $doc['identifier'] ) : ?>Allerta meteo n. <?php echo esc_html( $doc['identifier'] ); ?><br><?php endif; ?>
								<?php if ( $doc['sent'] ) : ?>Emissione: <?php echo esc_html( wp_date( 'd/m/Y \o\r\e H:i', $doc['sent'], $tz ) ); ?><br><?php endif; ?>
								<?php if ( ! empty( $doc['stale'] ) ) : ?><em>Servizio regionale momentaneamente non raggiungibile: ultimi dati disponibili.</em><?php endif; ?>
							</p>
						<?php elseif ( ! $doc ) : ?>
							<p class="amer__meta">Dati non disponibili al momento.</p>
						<?php endif; ?>

						<?php if ( $s['mostra_zona'] ) : ?>
							<p class="amer__meta">
								Zona <?php echo esc_html( $s['zona'] ); ?>
								<?php if ( $s['mostra_legenda'] ) : ?>
									– <button type="button" class="amer__link" data-amer-open="<?php echo esc_attr( $uid ); ?>-legenda" aria-haspopup="dialog">
										<svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true" focusable="false"><circle cx="6.5" cy="6.5" r="4.5" fill="none" stroke="currentColor" stroke-width="2"/><path d="M10 10l4 4" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/><path d="M6.5 4.5v4M4.5 6.5h4" stroke="currentColor" stroke-width="1.5"/></svg>
										[legenda]
									</button>
								<?php else : ?>
									– <?php echo esc_html( $zones[ $s['zona'] ] ); ?>
								<?php endif; ?>
							</p>
						<?php endif; ?>

						<?php if ( $s['mostra_link'] ) : ?>
							<a class="amer__btn amer__btn--dark" href="<?php echo esc_url( self::PORTAL ); ?>" target="_blank" rel="noopener">
								Vai al sito allerta meteo Emilia Romagna
								<svg viewBox="0 0 16 16" width="12" height="12" aria-hidden="true" focusable="false"><path d="M9 2h5v5M14 2 7 9M12 9.5V14H2V4h4.5" fill="none" stroke="currentColor" stroke-width="1.5"/></svg>
								<span class="amer__sr">(Apre il link in una nuova scheda)</span>
							</a>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<?php
			// Finestre modali.
			if ( $s['mostra_info'] ) {
				foreach ( $cols as $c ) {
					if ( ! $c['zone'] ) {
						continue;
					}
					$z     = $c['zone'];
					$title = $c['doc']['identifier'] ? 'Allerta meteo n. ' . $c['doc']['identifier'] : 'Allerta meteo del ' . wp_date( 'd/m', $c['date'], $tz );
					ob_start();
					if ( $z['headline'] ) {
						echo '<p><strong>' . esc_html( $z['headline'] ) . '</strong></p>';
					}
					if ( $z['description'] ) {
						echo wp_kses_post( wpautop( $z['description'] ) );
					} elseif ( $c['doc']['note'] ) {
						echo wp_kses_post( wpautop( $c['doc']['note'] ) );
					} else {
						echo '<p>Nessuna nota per questa giornata.</p>';
					}
					if ( $z['onset'] && $z['expires'] ) {
						echo '<p class="amer__small">Validità: dal ' . esc_html( wp_date( 'd/m/Y H:i', $z['onset'], $tz ) ) . ' al ' . esc_html( wp_date( 'd/m/Y H:i', $z['expires'], $tz ) ) . '</p>';
					}
					echo self::dialog( $uid . '-' . $c['id'], $title, ob_get_clean() ); // phpcs:ignore
				}
			}
			if ( $s['mostra_zona'] && $s['mostra_legenda'] ) {
				$list = '<ul class="amer__zones">';
				foreach ( $zones as $code => $name ) {
					$cur   = $code === $s['zona'];
					$list .= '<li' . ( $cur ? ' class="is-current" aria-current="true"' : '' ) . '><strong>' . esc_html( $code ) . ':</strong> ' . esc_html( $name ) . '</li>';
				}
				$list .= '</ul>';
				echo self::dialog( $uid . '-legenda', 'Le ' . count( $zones ) . ' zone di allerta', $list ); // phpcs:ignore
			}
			?>
		</section>
		<?php
		return ob_get_clean();
	}

	private static function dialog( $id, $title, $html ) {
		return '<dialog class="amer__dialog" id="' . esc_attr( $id ) . '" aria-labelledby="' . esc_attr( $id ) . '-h">'
			. '<div class="amer__dialog-head"><h3 id="' . esc_attr( $id ) . '-h">' . esc_html( $title ) . '</h3>'
			. '<button type="button" class="amer__close" data-amer-close aria-label="Chiudi">&times;</button></div>'
			. '<div class="amer__dialog-body">' . $html . '</div></dialog>';
	}

	/** Variabili CSS dalle impostazioni. */
	private static function style_vars( array $s ) {
		$v = array(
			'--amer-bg'       => $s['sfondo'],
			'--amer-text'     => $s['testo'],
			'--amer-table-bg' => $s['tabella'],
			'--amer-border'   => $s['bordo'],
			'--amer-chip-bg'  => $s['chip'],
			'--amer-waves'    => $s['onde'],
			'--amer-max'      => absint( $s['larghezza'] ) ? absint( $s['larghezza'] ) . 'px' : 'none',
			'--amer-logo-h'   => absint( $s['logo_h'] ) . 'px',
		);
		for ( $i = 0; $i <= 3; $i++ ) {
			$v[ '--amer-l' . $i ] = $s[ 'lv_color_' . $i ];
		}
		if ( $s['sfondo_img'] ) {
			$v['--amer-bg-img']     = 'url("' . esc_url_raw( $s['sfondo_img'] ) . '")';
			$v['--amer-bg-overlay'] = min( 100, absint( $s['sfondo_overlay'] ) ) / 100;
		}
		$out = '';
		foreach ( $v as $k => $val ) {
			if ( '' !== (string) $val ) {
				$out .= $k . ':' . $val . ';';
			}
		}
		return $out;
	}

	/** Campana con onde (SVG). */
	private static function bell_svg( $level, array $s ) {
		$bell_color = $level >= 0 ? 'var(--amer-l' . $level . ')' : 'var(--amer-waves)';
		// Onde accese in base al livello (0, 2, 4, 6) se l'opzione è attiva.
		$lit = $s['onde_livello'] && $level > 0 ? $level * 2 : 0;

		$cx   = 30;
		$cy   = 90;
		$arcs = '';
		for ( $i = 0; $i < 6; $i++ ) {
			$r   = 52 + $i * 27;          // raggio crescente
			$h   = 18 + $i * 12.5;        // mezza altezza dell'onda
			$a   = asin( min( 0.95, $h / $r ) );
			$x1  = $cx + $r * cos( $a );
			$y1  = $cy - $r * sin( $a );
			$y2  = $cy + $r * sin( $a );
			$sw  = 13 + $i * 1.4;
			$col = $i < $lit ? 'var(--amer-l' . $level . ')' : 'var(--amer-waves)';
			$arcs .= sprintf(
				'<path d="M%.1f %.1fA%d %d 0 0 1 %.1f %.1f" stroke="%s" stroke-width="%.1f"/>',
				$x1, $y1, $r, $r, $x1, $y2, $col, $sw
			);
		}
		return '<svg viewBox="0 0 240 180" focusable="false" fill="none" stroke-linecap="round">'
			. '<g transform="translate(0 63) scale(2.2)" fill="' . $bell_color . '">'
			. '<path d="M12 2.2c.8 0 1.4.6 1.4 1.4v.7c2.9.7 4.8 3.3 4.8 6.4v3.7l1.9 2.6c.4.6 0 1.4-.7 1.4H4.6c-.7 0-1.1-.8-.7-1.4l1.9-2.6v-3.7c0-3.1 1.9-5.7 4.8-6.4v-.7c0-.8.6-1.4 1.4-1.4z"/>'
			. '<path d="M9.3 19.6h5.4a2.7 2.7 0 0 1-5.4 0z"/></g>'
			. $arcs . '</svg>';
	}

	/* ------------------------------------------------------------------ *
	 * Amministrazione
	 * ------------------------------------------------------------------ */

	public static function admin_menu() {
		add_options_page( 'SafeER', 'SafeER Allerte', 'manage_options', 'safeer', array( __CLASS__, 'settings_page' ) );
	}

	public static function admin_init() {
		register_setting( 'amer', self::OPTION, array(
			'type'              => 'array',
			'sanitize_callback' => array( __CLASS__, 'sanitize' ),
			'default'           => self::defaults(),
		) );
	}

	public static function admin_assets( $hook ) {
		if ( 'settings_page_safeer' !== $hook ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_add_inline_script( 'wp-color-picker', <<<'JS'
jQuery(function($){
	$('.amer-color').wpColorPicker();
	$('.amer-media').on('click', function(e){
		e.preventDefault();
		var target = $('#' + $(this).data('target'));
		var frame = wp.media({ title: 'Scegli immagine', button: { text: 'Usa questa immagine' }, library: { type: 'image' }, multiple: false });
		frame.on('select', function(){
			var a = frame.state().get('selection').first().toJSON();
			target.val(a.url).trigger('change');
		});
		frame.open();
	});
	$('.amer-media-clear').on('click', function(e){
		e.preventDefault();
		$('#' + $(this).data('target')).val('').trigger('change');
	});
	$('.amer-img-input').on('change', function(){
		var p = $('#' + this.id + '-preview');
		this.value ? p.attr('src', this.value).show() : p.hide();
	});
	$('#amer-reset').on('click', function(e){
		if (!confirm('Ripristinare tutti i valori predefiniti?')) e.preventDefault();
	});
});
JS
		);
	}

	public static function sanitize( $in ) {
		$in = (array) $in;
		if ( ! empty( $in['__reset'] ) ) {
			self::flush_cache();
			return self::defaults();
		}
		$d   = self::defaults();
		$out = array();

		$colors = array( 'sfondo', 'testo', 'tabella', 'bordo', 'chip', 'onde', 'lv_color_0', 'lv_color_1', 'lv_color_2', 'lv_color_3' );
		$ints   = array( 'cache' => array( 5, 720 ), 'logo_h' => array( 10, 200 ), 'larghezza' => array( 0, 3000 ), 'sfondo_overlay' => array( 0, 100 ) );
		$urls   = array( 'logo', 'sfondo_img' );

		foreach ( $d as $key => $default ) {
			$val = isset( $in[ $key ] ) ? $in[ $key ] : null;
			if ( in_array( $key, $colors, true ) ) {
				$out[ $key ] = sanitize_hex_color( (string) $val ) ?: $default;
			} elseif ( isset( $ints[ $key ] ) ) {
				$out[ $key ] = null === $val ? $default : min( $ints[ $key ][1], max( $ints[ $key ][0], absint( $val ) ) );
			} elseif ( in_array( $key, $urls, true ) ) {
				$out[ $key ] = $val ? esc_url_raw( $val ) : '';
			} elseif ( 'zona' === $key ) {
				$out[ $key ] = isset( self::zones()[ $val ] ) ? $val : $default;
			} elseif ( 'livello_su' === $key ) {
				$out[ $key ] = in_array( $val, array( 'oggi', 'entrambi' ), true ) ? $val : $default;
			} elseif ( is_int( $default ) ) {
				$out[ $key ] = empty( $val ) ? 0 : 1; // checkbox
			} else {
				$out[ $key ] = null === $val ? $default : sanitize_text_field( $val );
			}
		}
		self::flush_cache();
		return $out;
	}

	public static function flush_cache() {
		// Cambiando il "sale" le chiavi precedenti non vengono più lette e scadono da sole.
		update_option( 'amer_cache_salt', (int) get_option( 'amer_cache_salt', 1 ) + 1, false );
	}

	public static function flush_cache_action() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'amer_flush' ) ) {
			wp_die( 'Operazione non consentita.' );
		}
		self::flush_cache();
		wp_safe_redirect( admin_url( 'options-general.php?page=safeer&amer_flushed=1' ) );
		exit;
	}

	/* Helper per i campi del form. */
	private static function n( $key ) {
		return self::OPTION . '[' . $key . ']';
	}

	private static function f_text( $s, $key, $class = 'regular-text', $ph = '' ) {
		printf( '<input type="text" class="%s" id="amer-%s" name="%s" value="%s" placeholder="%s">', esc_attr( $class ), esc_attr( $key ), esc_attr( self::n( $key ) ), esc_attr( $s[ $key ] ), esc_attr( $ph ) );
	}

	private static function f_num( $s, $key, $min, $max, $suffix = '' ) {
		printf( '<input type="number" class="small-text" id="amer-%s" name="%s" value="%d" min="%d" max="%d"> %s', esc_attr( $key ), esc_attr( self::n( $key ) ), (int) $s[ $key ], absint( $min ), absint( $max ), esc_html( $suffix ) );
	}

	private static function f_color( $s, $key ) {
		printf( '<input type="text" class="amer-color" name="%s" value="%s" data-default-color="%s">', esc_attr( self::n( $key ) ), esc_attr( $s[ $key ] ), esc_attr( self::defaults()[ $key ] ) );
	}

	private static function f_check( $s, $key, $label ) {
		printf( '<label><input type="checkbox" name="%s" value="1" %s> %s</label><br>', esc_attr( self::n( $key ) ), checked( $s[ $key ], 1, false ), esc_html( $label ) );
	}

	private static function f_image( $s, $key ) {
		$id = 'amer-' . $key;
		printf(
			'<input type="url" class="regular-text amer-img-input" id="%1$s" name="%2$s" value="%3$s"> '
			. '<button class="button amer-media" data-target="%1$s">Scegli dalla libreria</button> '
			. '<button class="button-link amer-media-clear" data-target="%1$s">Rimuovi</button><br>'
			. '<img id="%1$s-preview" src="%3$s" alt="" style="margin-top:8px;max-height:60px;background:#2a3036;padding:6px;%4$s">',
			esc_attr( $id ),
			esc_attr( self::n( $key ) ),
			esc_attr( $s[ $key ] ),
			$s[ $key ] ? '' : 'display:none'
		);
	}

	public static function settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s = self::settings();
		?>
		<div class="wrap">
			<h1>SafeER Visualizzazione Allerte Emilia Romagna</h1>
			<?php if ( isset( $_GET['amer_flushed'] ) ) : // phpcs:ignore ?>
				<div class="notice notice-success is-dismissible"><p>Cache svuotata.</p></div>
			<?php endif; ?>

			<h2>Anteprima</h2>
			<div style="max-width:1200px;margin-bottom:24px">
				<?php echo self::render( $s ); // phpcs:ignore ?>
			</div>

			<form method="post" action="options.php">
				<?php settings_fields( 'amer' ); ?>

				<h2 class="title">Comune e dati</h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="amer-zona">Zona di allerta</label></th>
						<td>
							<select id="amer-zona" name="<?php echo esc_attr( self::n( 'zona' ) ); ?>">
								<?php foreach ( self::zones() as $code => $name ) : ?>
									<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $s['zona'], $code ); ?>><?php echo esc_html( $code . ' – ' . $name ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description">I Comuni dell'Unione delle Terre d'Argine (Carpi, Campogalliano, Novi, Soliera) sono in F1 – Pianura modenese.</p>
						</td>
					</tr>
					<tr><th scope="row"><label for="amer-comune">Nome del Comune</label></th><td><?php self::f_text( $s, 'comune', 'regular-text', 'es. Campogalliano' ); ?><p class="description">Usato nel testo alternativo del logo e per gli screen reader.</p></td></tr>
					<tr><th scope="row"><label for="amer-titolo">Titolo</label></th><td><?php self::f_text( $s, 'titolo' ); ?></td></tr>
					<tr><th scope="row"><label for="amer-cache">Aggiornamento dati</label></th><td><?php self::f_num( $s, 'cache', 5, 720, 'minuti di cache' ); ?></td></tr>
				</table>

				<h2 class="title">Logo</h2>
				<table class="form-table" role="presentation">
					<tr><th scope="row"><label for="amer-logo">Logo</label></th><td><?php self::f_image( $s, 'logo' ); ?><p class="description">PNG o SVG, meglio se chiaro su fondo trasparente. Lascia vuoto per non mostrarlo.</p></td></tr>
					<tr><th scope="row"><label for="amer-logo_h">Altezza logo</label></th><td><?php self::f_num( $s, 'logo_h', 10, 200, 'px' ); ?></td></tr>
				</table>

				<h2 class="title">Sfondo e colori</h2>
				<table class="form-table" role="presentation">
					<tr><th scope="row">Colore di sfondo</th><td><?php self::f_color( $s, 'sfondo' ); ?></td></tr>
					<tr><th scope="row"><label for="amer-sfondo_img">Immagine di sfondo</label></th><td><?php self::f_image( $s, 'sfondo_img' ); ?></td></tr>
					<tr><th scope="row"><label for="amer-sfondo_overlay">Scurimento immagine</label></th><td><?php self::f_num( $s, 'sfondo_overlay', 0, 100, '% (velo del colore di sfondo sopra l\'immagine, per la leggibilità)' ); ?></td></tr>
					<tr><th scope="row">Colore del testo</th><td><?php self::f_color( $s, 'testo' ); ?></td></tr>
					<tr><th scope="row">Sfondo tabella</th><td><?php self::f_color( $s, 'tabella' ); ?></td></tr>
					<tr><th scope="row">Bordi</th><td><?php self::f_color( $s, 'bordo' ); ?></td></tr>
					<tr><th scope="row">Sfondo etichetta livello</th><td><?php self::f_color( $s, 'chip' ); ?></td></tr>
					<tr><th scope="row">Colore onde</th><td><?php self::f_color( $s, 'onde' ); ?></td></tr>
					<tr><th scope="row"><label for="amer-larghezza">Larghezza massima contenuto</label></th><td><?php self::f_num( $s, 'larghezza', 0, 3000, 'px (0 = tutta la larghezza)' ); ?></td></tr>
					<tr><th scope="row">Carattere</th><td><?php self::f_check( $s, 'font', 'Carica Titillium Web da Google Fonts (non serve se il tema è già Bootstrap Italia)' ); ?></td></tr>
				</table>

				<h2 class="title">Livelli di allerta</h2>
				<table class="widefat striped" style="max-width:820px">
					<thead><tr><th>Livello</th><th>Colore</th><th>Etichetta (tabella)</th><th>Descrizione (livello complessivo)</th></tr></thead>
					<tbody>
						<?php foreach ( array( 'Verde', 'Gialla', 'Arancione', 'Rossa' ) as $i => $name ) : ?>
							<tr>
								<td><?php echo esc_html( $name ); ?></td>
								<td><?php self::f_color( $s, 'lv_color_' . $i ); ?></td>
								<td><?php self::f_text( $s, 'lv_label_' . $i, 'regular-text' ); ?></td>
								<td><?php self::f_text( $s, 'lv_desc_' . $i, 'regular-text' ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<h2 class="title">Fenomeni</h2>
				<table class="widefat striped" style="max-width:820px">
					<thead><tr><th>Mostra</th><th>Fenomeno (codice regionale)</th><th>Etichetta</th></tr></thead>
					<tbody>
						<?php foreach ( self::phenomena() as $code => $label ) : ?>
							<tr>
								<td><input type="checkbox" name="<?php echo esc_attr( self::n( 'fen_' . $code ) ); ?>" value="1" <?php checked( $s[ 'fen_' . $code ], 1 ); ?> aria-label="Mostra <?php echo esc_attr( $label ); ?>"></td>
								<td><code><?php echo esc_html( $code ); ?></code></td>
								<td><?php self::f_text( $s, 'fen_label_' . $code, 'regular-text' ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<h2 class="title">Visualizzazione</h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">Elementi</th>
						<td>
							<?php
							self::f_check( $s, 'domani', 'Colonna di domani (quando il documento è già emesso)' );
							self::f_check( $s, 'mostra_campana', 'Campana con onde' );
							self::f_check( $s, 'onde_livello', 'Colora le onde in base al livello (gialla 2, arancione 4, rossa 6 onde)' );
							self::f_check( $s, 'mostra_info', 'Pulsanti "Info" con le note del documento' );
							self::f_check( $s, 'mostra_emiss', 'Numero e data di emissione' );
							self::f_check( $s, 'mostra_zona', 'Zona di allerta' );
							self::f_check( $s, 'mostra_legenda', 'Link alla legenda delle zone' );
							self::f_check( $s, 'mostra_link', 'Pulsante al sito Allerta Meteo Emilia-Romagna' );
							self::f_check( $s, 'nascondi_na', 'Nascondi i fenomeni non valutati per la zona (es. mare nell\'entroterra)' );
							?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="amer-livello_su">Livello complessivo calcolato su</label></th>
						<td>
							<select id="amer-livello_su" name="<?php echo esc_attr( self::n( 'livello_su' ) ); ?>">
								<option value="oggi" <?php selected( $s['livello_su'], 'oggi' ); ?>>Solo oggi</option>
								<option value="entrambi" <?php selected( $s['livello_su'], 'entrambi' ); ?>>Oggi e domani (il più alto)</option>
							</select>
							<p class="description">Il livello accanto alla campana è il più alto tra tutti i fenomeni delle giornate considerate.</p>
							<?php self::f_check( $s, 'mostra_giorno', 'Indica a quale giorno si riferisce (es. "riferito a domani 28/10")' ); ?>
						</td>
					</tr>
					<tr><th scope="row"><label for="amer-testo_nd">Testo per dato non disponibile</label></th><td><?php self::f_text( $s, 'testo_nd', 'small-text' ); ?></td></tr>
				</table>

				<p class="submit">
					<?php submit_button( null, 'primary', 'submit', false ); ?>
					<button type="submit" class="button" id="amer-reset" name="<?php echo esc_attr( self::n( '__reset' ) ); ?>" value="1">Ripristina predefiniti</button>
				</p>
			</form>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="amer_flush">
				<?php wp_nonce_field( 'amer_flush' ); ?>
				<?php submit_button( 'Svuota cache e ricarica i dati', 'secondary', 'submit', false ); ?>
			</form>

			<h2>Come usarlo</h2>
			<p>Shortcode <code>[allerta_meteo]</code> in una pagina (blocco Shortcode) oppure widget <em>SafeER Allerte</em>.</p>
			<p>Ogni pagina può sovrascrivere le impostazioni:<br>
			<code>[allerta_meteo zona="F1" comune="Carpi" titolo="Allerta meteo" logo="https://…/logo.png" sfondo="#1d3557" testo="#ffffff" domani="si" campana="si" info="si" emissione="si" giorno="si" legenda="si" link="si"]</code></p>
		</div>
		<?php
	}
}

/** Widget classico. */
class Allerta_Meteo_ER_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct( 'allerta_meteo_er', 'SafeER Allerte', array( 'description' => 'Allerta meteo regionale per la zona del Comune.' ) );
	}

	public function widget( $args, $instance ) {
		$s = Allerta_Meteo_ER::settings();
		if ( ! empty( $instance['zona'] ) ) {
			$s['zona'] = $instance['zona'];
		}
		$s['mostra_campana'] = empty( $instance['campana'] ) ? 0 : 1;
		echo $args['before_widget']; // phpcs:ignore
		echo Allerta_Meteo_ER::render( $s ); // phpcs:ignore
		echo $args['after_widget']; // phpcs:ignore
	}

	public function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'zona' => '', 'campana' => 0 ) );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'zona' ) ); ?>">Zona:</label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'zona' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'zona' ) ); ?>">
				<option value="">Come nelle impostazioni</option>
				<?php foreach ( Allerta_Meteo_ER::zones() as $code => $name ) : ?>
					<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $instance['zona'], $code ); ?>><?php echo esc_html( $code . ' – ' . $name ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p><label><input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'campana' ) ); ?>" value="1" <?php checked( $instance['campana'] ); ?>> Mostra la campana</label></p>
		<p class="description">Grafica e altri parametri: Impostazioni › SafeER Allerte.</p>
		<?php
	}

	public function update( $new, $old ) {
		return array(
			'zona'    => isset( $new['zona'], Allerta_Meteo_ER::zones()[ $new['zona'] ] ) ? $new['zona'] : '',
			'campana' => empty( $new['campana'] ) ? 0 : 1,
		);
	}
}

Allerta_Meteo_ER::init();
