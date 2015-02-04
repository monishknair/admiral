<?php
/**
 * "Result" page.
 *
 * @author Timo Tijhof, 2012
 * @since 1.0.0
 * @package TestSwarm
 */

class ResultPage extends Page {

	protected $foundEmpty = false;

	public function execute() {
		// Handle 'raw' output
		$request = $this->getContext()->getRequest();

		$resultsID = $request->getInt( 'item' );
		$isRaw = $request->getBool( 'raw' );

		if ( $resultsID && $isRaw ) {
			$this->serveRawResults( $resultsID );
			exit;
		}

		// Regular request
		$action = ResultAction::newFromContext( $this->getContext() );
		$action->doAction();

		$this->setAction( $action );
		$this->content = $this->initContent();
	}

	protected function initContent() {
		$request = $this->getContext()->getRequest();
		$resultsID = $request->getInt( 'item' );

		$this->setTitle( 'Run result' );
		$this->setRobots( 'noindex,nofollow' );
		$this->bodyScripts[] = swarmpath( 'js/result.js' );

		$error = $this->getAction()->getError();
		$data = $this->getAction()->getData();
		$html = '';

		if ( $error ) {
			$html .= html_tag( 'div', array( 'class' => 'alert alert-error' ), $error['info'] );
			return $html;
		}

		$this->setSubTitle( '#' . $data['info']['id'] );


		if ( $data['job'] ) {
			$html = '<p><em>'
				. html_tag_open( 'a', array( 'href' => $data['job']['url'], 'title' => 'Back to Job #' . $data['job']['id'] ) ) . '&laquo Back to Job #' . $data['job']['id'] . '</a>'
				. '</em></p>';
		} else {
			$html = '<p><em>Run #' . $data['info']['runID'] . ' has been deleted. Job info unavailable.</em></p>';
		}

		if ( $data['otherRuns'] ) {
			$html .= '<table class="table table-bordered swarm-results"><thead>'
				. JobPage::getUaHtmlHeader( $data['otherRuns']['userAgents'] )
				. '</thead><tbody>'
				. JobPage::getUaRunsHtmlRows( $data['otherRuns']['runs'], $data['otherRuns']['userAgents'] )
				. '</tbody></table>';
		}

		$html .= '<h3>Information</h3>'
			. '<table class="table table-striped">'
			. '<tbody>'
			. '<tr><th>Run</th><td>'
				. ($data['job']
					? html_tag( 'a', array( 'href' => $data['job']['url'] ), 'Job #' . $data['job']['id'] ) . ' / '
					: ''
				)
				. 'Run #' . htmlspecialchars( $data['info']['runID'] )
			. '</td></tr>'
  . '<tr><th>Remote URL</th><td>'
    . ($data['result_url']
    ? html_tag( 'a', array( 'href' => $data['result_url'] ), $data['result_url'] )
    : ''
    )    . '</td></tr>'
  . '<tr><th>Build URL</th><td>'
    . ($data['build_url']
    ? html_tag( 'a', array( 'href' => $data['build_url'] ), $data['build_url'] )
    : ''
    )    . '</td></tr>'
			. '<tr><th>UA ID</th><td>'
				. '<code>' . htmlspecialchars( $data['client']['uaID'] ) . '</code>'
			. '<tr><th>Started</th><td>'
				. self::getPrettyDateHtml( $data['info'], 'started' )
			. '</td></tr>'
			. '</tbody></table>';

		$html .= '<h3>Results</h3>'
			. '<p class="swarm-toollinks">'
			. html_tag( 'a', array(
				'href' => $data['result_url'],
				'target' => '_blank'
			), 'Open in new window' )
			. '</p>'
			. html_tag( 'iframe', array(
				'src' => $data['result_url'],
				'width' => '100%',
        'height' => '1200px',
				'class' => 'swarm-result-frame',
			));


		return $html;
	}

	protected function serveRawResults( $resultsID ) {
		$db = $this->getContext()->getDB();

		$this->setRobots( 'noindex,nofollow' );

		// Override frameoptions to allow framing. Note, we can't use
		// Page::setFrameOptions(), as this page does not use the Page class layout.
		header( 'X-Frame-Options: SAMEORIGIN', true );

		$row = $db->getRow(str_queryf(
			'SELECT
				status,
				report_html
			FROM runresults
			WHERE id = %u;',
			$resultsID
		));

		header( 'Content-Type: text/html; charset=utf-8' );
		if ( $row ) {
			$status = intval( $row->status );
			// If it finished or was aborted, there should be
			// a (at least partial) html report.
			if ( $status === ResultAction::$STATE_FINISHED || $status === ResultAction::$STATE_ABORTED ) {
				if ( $row->report_html !== '' && $row->report_html !== null ) {
					header( 'Content-Encoding: gzip' );
					echo $row->report_html;
				} else {
					$this->outputMini(
						'No Content',
						'Client saved results but did not attach an HTML report.'
					);
				}

			// Client timed-out
			} elseif ( $status === ResultAction::$STATE_LOST ) {
				$this->outputMini(
					'Client Lost',
					'Client lost connection with the swarm.'
				);

			// Still busy? Or some unknown status?
			} else {
				$this->outputMini(
					'In Progress',
					'Client did not submit results yet. Please try again later.'
				);
			}
		} else {
			self::httpStatusHeader( 404 );
			$this->outputMini( 'Not Found' );
		}

		// This is a raw HTML response, the Page should not build.
		exit;
	}
}
