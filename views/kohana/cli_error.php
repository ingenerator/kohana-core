<?php
/**
 * CLI generic error renderer for uncaught exceptions and fatal errors. Shows the error, source and limited trace for
 * debugging.
 *
 * Could be significantly cleaned up by implementing internal replacements for the \Debug:: methods that do not output
 * HTML.
 */
$cols = exec('tput cols', $status, $result);
if ($result !== 0) {
    // Probably running under cron or otherwise detached from a terminal
    $cols = 100;
}
?>

ERROR:     <?= $class; ?> [<?= $code; ?>]: <?= $message; ?>


Called at: <?= Debug::path($file).'['.$line.']'; ?>

-------
Source:
<?php
// Render the source that failed, highlighting the failing line
$source = html_entity_decode(strip_tags(Debug::source($file, $line)));
echo preg_replace_callback(
    "/^([0-9]+).*$/m",
    function ($matches) use ($line) {
        $prefix = ($line === (int) $matches[1]) ? '>>>>' : '    ';

        return $prefix.$matches[0];
    },
    $source
);
?>

TRACE (last 5 entries)
<?php
$level = -1;
foreach (Debug::trace($trace) as $i => $step) {
    $level++;
    $file = $step['file'] ? Debug::path($step['file']).'['.$step['line'].']'
        : '{PHP internal call}';
    echo str_pad("#".$level, 3)." ".$step['function']." - ".$file.\PHP_EOL;

    if ( ! $step['args']) {
        continue;
    }
    $max_name_len = max(
        array_map(function ($key) { return strlen($key); }, array_keys($step['args']))
    );
    $indent       = str_repeat(" ", $max_name_len + 5);
    $wrap_at      = $cols - strlen($indent);

    foreach ($step['args'] as $name => $arg) {
        $name = str_pad(is_numeric($name) ? "#".$name : "$".$name, $max_name_len + 1);
        echo "  ".$name."  ";
        // FATAL have already converted args to strings
        $arg = ($code === 'Fatal Error')
            ? $arg
            : html_entity_decode(
                strip_tags(Debug::dump($arg)),
                ENT_QUOTES,
                'UTF-8'
            );
        $arg = explode(\PHP_EOL, wordwrap($arg, $wrap_at, \PHP_EOL, TRUE));

        // Don't allow ridiculously long output if the method took a lot of args or a big array
        if (count($arg) > 20) {
            $truncated_count = count($arg) - 20;
            $arg             = array_slice($arg, 0, 20);
            $arg[]           = '<<<--- '.$truncated_count.' lines truncated --->>>';
        }
        $arg = implode(\PHP_EOL.$indent, $arg);

        echo $arg.\PHP_EOL;
    }
    if ($level >= 5) {
        break;
    }
}
