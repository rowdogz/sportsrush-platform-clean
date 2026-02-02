<?php

/*
 * Football Pool WordPress plugin
 *
 * @copyright Copyright (c) 2024 Antoine Hurkmans
 * @link https://wordpress.org/plugins/football-pool/
 * @license https://plugins.svn.wordpress.org/football-pool/trunk/COPYING
 *
 * This file is part of Football pool.
 *
 * Football pool is free software: you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software Foundation,
 * either version 3 of the License, or (at your option) any later version.
 *
 * Football pool is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
 * PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with Football pool.
 * If not, see <https://www.gnu.org/licenses/>.
 */

// Based on Highcharts
class Football_Pool_Chart {
	public string $ID;
	public string $type; // pie, line, etc.
	public array $data = [];
	public $width;
	public $height;
	public string $title = '';
	public array $options = [];
	public array $JS_options = []; // use these to extend default options, or overwrite values (use JS dot-notation)
	public string $custom_css = '';
	public string $css_class = 'chart';
	public int $x_axis_step = 1;
	public bool $API_loaded = false;
	public bool $stats_enabled;
	
	/*
	 * Omit width and/or height to make the charts responsive (width = 100%).
	 * Or supply a width and height in pixels or a percentage to set the chart to a defined width.
	 */ 
	public function __construct( $id = 'chart1', $type = 'line', $width = null, $height = null ) {
		$this->ID = $id;
		$this->width = $width;
		$this->height = $height;
		$this->type = $type;
		
		$this->stats_enabled = ( Football_Pool_Utils::get_fp_option( 'use_charts', 0, 'int' ) === 1 
						&& Football_Pool_Utils::get_fp_option( 'simple_calculation_method', 0, 'int' ) === 0 );
		$this->API_loaded = $this->stats_enabled 
						&& file_exists( WP_PLUGIN_DIR . FOOTBALLPOOL_HIGHCHARTS_API );
	}
		
	public function draw() {
		if ( ! $this->stats_enabled ) {
			return '<p class="error">' . __( 'Charts cannot be displayed.', 'football-pool' ) . '</p>';
		}
		
		$output = '';
		
		if ( $this->custom_css != '' ) 
			$this->css_class .= ' ' . $this->custom_css;
		
		$output .= $this->render_base_HTML();
		switch ( $this->type ) {
			case 'line':
				$this->line_definition();
				break;
			case 'bar':
				// not yet implemented
				break;
			case 'column':
				$this->column_definition();
				break;
			case 'pie':
				$this->pie_definition();
				break;
			default:
				break;
		}

		$output .= $this->render_options();
		$output .= $this->finish_chart();
		
		return apply_filters( 'footballpool_chart_html', $output );
	}
	
	protected function render_base_HTML(): string
	{
		$output = '';
		
		if ( $this->width === null ) {
			$output .= sprintf(
				'<div id="%s-wrapper" class="chart-wrapper %s-chart %s">',
				$this->ID, $this->type, $this->css_class
			);
		} else {
			$output .= sprintf(
				'<div id="%s-wrapper" class="chart-wrapper %s-chart %s" style="width:%s;height:%s;">',
				$this->ID, $this->type, $this->css_class, $this->width, $this->height
			);
		}
		
		if ( is_numeric( $this->width ) && is_numeric( $this->height ) ) {
			$output .= sprintf(
				'<div id="%s" style="width:%dpx; height:%dpx;"></div>',
				$this->ID, $this->width, $this->height
			);
		} else {
			$output .= sprintf(
				'<div id="%s" class="chart-inner"><div style="width:100%%; height:100%%;"></div></div>',
				$this->ID
			);
		}
		$output .= '</div>';
		$output .= sprintf( "\n<script type='text/javascript'>
							let chart_%s;
							jQuery( document ).ready( function() {
								let options = {
									accessibility: {
										enabled: false
                                    },
									chart: {
										type: '%s',
										renderTo: '%s',
										plotBackgroundColor: null,
										plotBorderWidth: 1,
										plotShadow: false
									}
									,title: {
										text: '%s'
									}\n",
							$this->ID, $this->type, $this->ID, $this->title
						);
		return $output;
	}
	
	protected function finish_chart(): string
	{
		$output = "				}; // end options JSON\n";

		$output .= $this->render_JS_options();
		
		$output .= sprintf( "chart_%s = new Highcharts.Chart( options );\n} );\n</script>\n", $this->ID );

		return $output;
	}
	
	protected function series_data_template( $name, $data = [], $type = '', $options = [] ): string
	{
		$output = "{ name: '" . $name . "', data: " . json_encode( $data );
		
		if ( count( $options ) > 0 ) $output .= implode(", ", $options) . ", ";
		if ( $type !== '' ) $output .= ", type: '" . $type . "'";
		
		$output .= " }";
		
		return $output;
	}
	
	protected function render_JS_options(): string
	{
		$output = '';
		if ( count( $this->JS_options ) > 0 ) {
			$output .= implode( ";", $this->JS_options );
			$output .= ";\n";
		}
		return $output;
	}
	
	protected function render_options(): string
	{
		$output = '';
		if ( count( $this->options ) > 0 ) {
			$output .= ",\n";
			$output .= implode( ",", $this->options );
		}
		return "{$output}\n";
	}

	/**
	 * @return void
	 */
	protected function column_definition() {
		$this->options[] = "plotOptions: {
								series: {
									minPointLength: 3
								}
							}";
		$this->options[] = "yAxis: {
								title: { text: null }, 
								showFirstLabel: true, 
								startOnTick: true,
								allowDecimals: false
							}";
//		$this->JS_options[] = "options.chart.defaultSeriesType = 'column'";
		$series = [];
		foreach ( $this->data as $key => $data_row ) {
			if ( array_key_exists( 'user_name', $data_row ) ) {
				$name = $data_row['user_name'];
				$data = $data_row['data'];
			} else {
				$name = $key;
				$data = $data_row;
			}
			$series[] = $this->series_data_template( $name, $data );
		}
		$this->options[] = "series: [" . implode( ',', $series ) . "]";
	}

	/**
	 * @return void
	 */
	protected function pie_definition() {
		$this->options[] = "tooltip: {
								formatter: function() {
									return '<b>' + this.point.name + '</b>: ' 
											+ this.y + ' (' + this.percentage.toFixed(0) + ' %)';
								}
							}";
		$this->options[] = "plotOptions: {
								pie: {
									allowPointSelect: true,
									cursor: 'pointer',
									dataLabels: {
										enabled: false
									},
									showInLegend: true
								}
							}";
		$this->options[] = "series: [" . $this->series_data_template( 'scores', $this->data, 'pie' ) . "]";
	}

	/**
	 * @return void
	 */
	protected function line_definition() {
		$this->options[] = "plotOptions: {
								series: {
									marker: {
										enabled: false,
										states: {
											hover: {
												enabled: true
											}
										}
									}
								}
							}";
		$this->options[] = sprintf( "yAxis: {
										title: { text: '%s' }, 
										min: 0, 
										showFirstLabel: true, 
										startOnTick: false,
										allowDecimals: false
									}"
									, __( 'points', 'football-pool' )
							);
		$this->options[] = sprintf( "xAxis: { 
										allowDecimals: false,
										title: { text: '%s' }, 
										labels: { 
											enabled: true
											//,rotation: -45
											//,align: 'right'
										}
									}"
									, __( 'matches and questions', 'football-pool' ) 
							);
		$this->options[] = sprintf( "subtitle: { text: document.ontouchstart === undefined ? '%s' : '%s' }"
									, __( 'Click and drag in the plot area to zoom in', 'football-pool' )
									, __( 'Drag your finger over the plot to zoom in', 'football-pool' )
							);
		$this->JS_options[] = "options.chart.zoomType = 'x'";
		
		$single_point = false;
		$series = [];
		foreach( $this->data['series'] as $data_row ) {
			if ( ! $single_point && count( $data_row['data'] ) === 1 ) {
				$single_point = true;
				$this->JS_options[] = "options.plotOptions.series.marker.enabled = true";
				$this->JS_options[] = "options.plotOptions.series.marker.symbol = 'circle'";
			}
			$series[] = $this->series_data_template( $data_row['name'], $data_row['data'] );
		}
		$this->JS_options[] = "var categories = " . json_encode( $this->data['categories'] );
		$this->options[] = "series: [" . implode(',', $series) . "]";
	}

	/**
	 * @return string
	 */
	public function remove_last_point_from_series(): string
	{
		return sprintf(
			"<script type='text/javascript'>
				jQuery( document ).ready( function() {
					jQuery.each( chart_%s.series, function() { this.data[this.data.length - 1].remove() } );
				} );
			</script>",
			$this->ID
		);
	}
}
