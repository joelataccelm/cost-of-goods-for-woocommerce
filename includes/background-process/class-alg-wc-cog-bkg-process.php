<?php
/**
 * Cost of Goods for WooCommerce - Background Process
 *
 * @version 2.3.0
 * @since   2.3.0
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_Cost_of_Goods_Bkg_Process' ) ) :

	class Alg_WC_Cost_of_Goods_Bkg_Process extends WP_Background_Process {

		protected $email_sending_params = array();

		/**
		 * get_logger_context.
		 *
		 * @version 2.3.0
		 * @since   2.3.0
		 *
		 * @return string
		 */
		protected function get_logger_context() {
			return $this->action;
		}

		/**
		 * get_action_label.
		 *
		 * @version 2.3.0
		 * @since   2.3.0
		 *
		 * @return string
		 */
		protected function get_action_label() {
			return $this->action;
		}

		/**
		 * task.
		 *
		 * @version 2.3.0
		 * @since   2.3.0
		 *
		 * @param mixed $item
		 *
		 * @return bool|mixed
		 */
		protected function task( $item ) {
			$logger = wc_get_logger();
			$logger->debug( sprintf( '%s', wp_json_encode( $item, true ) ), array( 'source' => $this->get_logger_context() ) );
			return false;
		}

		/**
		 * complete.
		 *
		 * @version 2.3.0
		 * @since   2.3.0
		 */
		protected function complete() {
			$logger = wc_get_logger();
			$logger->info( 'Task complete', array( 'source' => $this->get_logger_context() ) );
			$this->send_email();
			parent::complete(); // TODO: Change the autogenerated stub
		}

		/**
		 * send_email.
		 *
		 * @see https://gist.github.com/tameemsafi/81725f0b8687244e3f4fcf2a0e46662e
		 *
		 * @version 2.3.0
		 * @since   2.3.0
		 */
		protected function send_email() {
			if (
				empty( $email_params = $this->get_email_params() )
				|| ! $email_params['send_email_on_task_complete']
			) {
				return;
			}

			$subject = $this->get_email_subject();
			$message = $this->get_email_template();

			// Get woocommerce mailer from instance
			$mailer = WC()->mailer();

			// Wrap message using woocommerce html email template
			$wrapped_message = $mailer->wrap_message( $this->get_email_heading(), $message );

			// Create new WC_Email instance
			$wc_email = new WC_Email;

			// Style the wrapped message with woocommerce inline styles
			$html_message = $wc_email->style_inline( $wrapped_message );

			// Send the email using wordpress mail function
			wp_mail( $email_params['send_to'], $subject, $html_message, array( 'Content-Type: text/html; charset=UTF-8' ) );
		}

		/**
		 * get_email_template.
		 *
		 * @version 2.3.0
		 * @since   2.3.0
		 *
		 * @return mixed
		 */
		protected function get_email_template() {
			$email_params  = $this->get_email_params();
			$array_from_to = array_merge( $email_params['default_template_vars'], $email_params['template_vars'] );
			return $this->replace_variables( $array_from_to, $email_params['email_template'] );
		}

		/**
		 * replace_variables.
		 *
		 * @version 2.3.0
		 * @since   2.3.0
		 *
		 * @param $from_to
		 * @param $string
		 *
		 * @return mixed
		 */
		protected function replace_variables( $from_to, $string ) {
			return str_replace( array_keys( $from_to ), $from_to, $string );
		}

		/**
		 * get_email_subject.
		 *
		 * @version 2.3.0
		 * @since   2.3.0
		 *
		 * @return mixed
		 */
		protected function get_email_subject() {
			$email_params  = $this->get_email_params();
			$array_from_to = array_merge( $email_params['default_template_vars'], $email_params['template_vars'] );
			return $this->replace_variables( $array_from_to, $email_params['email_subject'] );
		}

		/**
		 * get_email_heading.
		 *
		 * @version 2.3.0
		 * @since   2.3.0
		 *
		 * @return mixed
		 */
		protected function get_email_heading() {
			$email_params  = $this->get_email_params();
			$array_from_to = array_merge( $email_params['default_template_vars'], $email_params['template_vars'] );
			return $this->replace_variables( $array_from_to, $email_params['email_heading'] );
		}

		/**
		 * get_email_params.
		 *
		 * @version 2.3.0
		 * @since   2.3.0
		 *
		 * @return array
		 */
		public function get_email_params() {
			$params = wp_parse_args( apply_filters( 'alg_wc_cog_bkg_process_email_params', $this->email_sending_params ), array(
				'send_email_on_task_complete' => true,
				'send_to'                     => get_option( 'admin_email' ),
				'email_subject'               => '{action_label}',
				'email_heading'               => '{action_label}',
				'email_template'              => 'Task complete',
				'default_template_vars'       => array( '{action_label}' => $this->get_action_label() ),
				'template_vars'               => array()
			) );
			return $params;
		}

		/**
		 * set_email_params.
		 *
		 * @version 2.3.0
		 * @since   2.3.0
		 *
		 * @param array $args
		 */
		public function set_email_params( $args = null ) {
			$this->email_sending_params = $args;
		}

		/**
		 * save.
		 *
		 * @version 2.3.0
		 * @since   2.3.0
		 */
		public function save() {
			$logger = wc_get_logger();
			$logger->info( sprintf( '%s task started running', $this->get_action_label() ), array( 'source' => $this->get_logger_context() ) );
			return parent::save(); // TODO: Change the autogenerated stub
		}

		/**
		 * dispatch.
		 *
		 * @version 2.3.0
		 * @since   2.3.0
		 */
		public function dispatch() {
			$logger     = wc_get_logger();
			$dispatched = parent::dispatch();
			if ( is_wp_error( $dispatched ) ) {
				$logger->error(
					sprintf( 'Unable to dispatch Background Process: %s', $dispatched->get_error_message() ),
					array( 'source' => $this->get_logger_context() )
				);
			}
		}

	}
endif;