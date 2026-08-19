@extends('email-templates.layout.app')

@push('order_feedback_style')
	<link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet" type="text/css"><!--<![endif]-->
	<style>
		* {
			box-sizing: border-box;
		}

		body {
			margin: 0;
			padding: 0;
		}

		a[x-apple-data-detectors] {
			color: inherit !important;
			text-decoration: inherit !important;
		}

		#MessageViewBody a {
			color: inherit;
			text-decoration: none;
		}

		p {
			line-height: inherit
		}

		.desktop_hide,
		.desktop_hide table {
			mso-hide: all;
			display: none;
			max-height: 0px;
			overflow: hidden;
		}

		.image_block img+div {
			display: none;
		}

		sup,
		sub {
			font-size: 75%;
			line-height: 0;
		}

		@media (max-width:620px) {
			.desktop_hide table.icons-inner {
				display: inline-block !important;
			}

			.icons-inner {
				text-align: center;
			}

			.icons-inner td {
				margin: 0 auto;
			}

			.image_block div.fullWidth {
				max-width: 100% !important;
			}

			.mobile_hide {
				display: none;
			}

			.row-content {
				width: 100% !important;
			}

			.stack .column {
				width: 100%;
				display: block;
			}

			.mobile_hide {
				min-height: 0;
				max-height: 0;
				max-width: 0;
				overflow: hidden;
				font-size: 0px;
			}

			.desktop_hide,
			.desktop_hide table {
				display: table !important;
				max-height: none !important;
			}

			.row-2 .column-1 .block-6.paragraph_block td.pad>div {
				font-size: 17px !important;
			}

			.row-2 .column-1 .block-5.spacer_block {
				height: 5px !important;
			}
		}
	</style>
@endpush

@section('content')
<div class="body" style="background-color: #e2e2e2; margin: 0; padding: 0; -webkit-text-size-adjust: none; text-size-adjust: none;" >
    
    	<table class="nl-container" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #e2e2e2;">
		<tbody>
			<tr>
				<td>
				
					<table class="row row-2" align="center" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
						<tbody>
							<tr>
								<td>
									<table class="row-content stack" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #ffffff; color: #000000; width: 600px; margin: 0 auto;" width="600">
										<tbody>
											<tr>
												<td class="column column-1" width="100%" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; padding-bottom: 20px; vertical-align: top;">
													<div class="spacer_block block-1" style="height:45px;line-height:45px;font-size:1px;">&#8202;</div>
													<table class="heading_block block-2" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
														<tr>
															<td class="pad" style="padding-left:20px;padding-right:20px;text-align:center;width:100%;">
																<h1 style="margin: 0; color: #2a3940; direction: ltr; font-family: Roboto, Tahoma, Verdana, Segoe, sans-serif; font-size: 23px; font-weight: normal; letter-spacing: normal; line-height: 1.2; text-align: center; margin-top: 0; margin-bottom: 0; mso-line-height-alt: 28px;"><span class="tinyMce-placeholder" style="word-break: break-word;">Hey <strong>{{ $order->customer->fname ?? 'Customer' }}</strong>,</span></h1>
															</td>
														</tr>
													</table>
													<table class="paragraph_block block-3" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;">
														<tr>
															<td class="pad" style="padding-bottom:10px;padding-left:20px;padding-right:20px;padding-top:15px;">
																<div style="color:#2a3940;font-family:Roboto, Tahoma, Verdana, Segoe, sans-serif;font-size:17px;line-height:1.2;text-align:center;mso-line-height-alt:20px;">
																	<p style="margin: 0; word-break: break-word;"><strong>We hope you enjoyed your meal!</strong></p>
																	<p style="margin: 0; word-break: break-word;">&nbsp;</p>
																	<p style="margin: 0; word-break: break-word;">Thank you so much for choosing us for your meal. Your feedback helps us keep improving and serve up the best Mexican flavors every time.</p>
																	<p style="margin: 0; word-break: break-word;">&nbsp;</p>
																	<p style="margin: 0; word-break: break-word;"><span style="word-break: break-word;">We’d love to hear your thoughts</span></p>
																</div>
															</td>
														</tr>
													</table>
													<table class="image_block block-4" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
														<tr>
															<td class="pad" style="width:100%;">
																<div class="alignment" align="center">
																	<div style="max-width: 255px;"><a href="https://www.trustpilot.com/review/catrinafreshmex.com" target="_blank"><img src="{{ asset('public/assets/email-icons/rateus.png') }}" style="display: block; height: auto; border: 0; width: 100%;" width="255" alt title height="auto"></a></div>
																</div>
															</td>
														</tr>
													</table>
													<div class="spacer_block block-5" style="height:45px;line-height:45px;font-size:1px;">&#8202;</div>
													<table class="paragraph_block block-6" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;">
														<tr>
															<td class="pad" style="padding-bottom:10px;padding-left:20px;padding-right:20px;padding-top:15px;">
																<div style="color:#2a3940;font-family:Roboto, Tahoma, Verdana, Segoe, sans-serif;font-size:18px;line-height:1.2;text-align:center;mso-line-height-alt:22px;">
																	<p style="margin: 0; word-break: break-word;">Leave a quick review and instantly enter to win exclusive, limited-edition merch — it’s our small way of saying thank you!</p>
																</div>
															</td>
														</tr>
													</table>
													<table class="image_block block-7" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
														<tr>
															<td class="pad" style="width:100%;padding-right:0px;padding-left:0px;">
																<div class="alignment" align="center">
																	<div class="fullWidth" style="max-width: 600px;"><img src="{{ asset('public/assets/email-icons/merch.png') }}" style="display: block; height: auto; border: 0; width: 100%;" width="600" alt title height="auto"></div>
																</div>
															</td>
														</tr>
													</table>
													<div class="spacer_block block-8" style="height:60px;line-height:60px;font-size:1px;">&#8202;</div>
												</td>
											</tr>
										</tbody>
									</table>
								</td>
							</tr>
						</tbody>
					</table>
					
					
					
					
				</td>
			</tr>
		</tbody>
	</table>
	
</div>

	@endsection