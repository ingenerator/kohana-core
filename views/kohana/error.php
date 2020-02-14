<?php
/**
 * Generic error page for errors on web. Note that this is NOT a full View model, and the template
 * and other classes are not available.
 * This view should be as simple as humanly possible to avoid any potential cascade in errors,
 *
 * @var Exception $e
 * @var array     $trace
 * @var string    $file
 * @var string    $line
 * @var string    $message
 * @var string    $code
 */

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Sorry, there was a problem</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
</head>

<body>
	<!-- Begin page content -->
	<div class="container">
		<div class="row">
			<div class="col-md-6 col-md-push-3">
				<div class="panel panel-danger">
					<div class="panel-heading">Sorry, there was an error processing your request</div>
					<div class="panel-body">
						<p>
							<strong><?=HTML::chars($message)?></strong>
							<small><?=HTML::chars($class)?></small>
						</p>
						<p>
							Please refresh your page to try again, or use the back button to go back.
							If this keeps happening please report it.
						</p>
					</div>
					<div class="panel-footer">
						<small>At <?=Debug::path($file)?> [ <?=HTML::chars($line);?> ] </small>
						<ul>
							<?php foreach($trace as $step): ?>
								<li>
									<small>
									<?php if (isset($step['file'])): ?>
										<?=Debug::path($step['file']) ?> [ <?=$step['line'] ?> ]
									<?php else: ?>
										{'PHP internal call'}
									<?php endif ?>
									</small>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>

				</div>
			</div>
		</div>
	</div>
	<!-- /container -->
</body>
</html>

