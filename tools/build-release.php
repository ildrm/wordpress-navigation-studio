<?php
/**
 * Create a deterministic plugin ZIP from a strict runtime allowlist.
 */

$root = dirname( __DIR__ );
$dist = $root . '/dist';
if ( ! is_dir( $root . '/build' ) ) {
	fwrite( STDERR, "Run npm run build first.\n" );
	exit( 1 );
}
if ( ! is_dir( $dist ) && ! mkdir( $dist, 0777, true ) && ! is_dir( $dist ) ) {
	throw new RuntimeException( 'Cannot create dist directory.' );
}
$target = $dist . '/navigation-studio-1.0.0.zip';
$zip    = new ZipArchive();
if ( true !== $zip->open( $target, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
	throw new RuntimeException( 'Cannot create release ZIP.' );
}
$paths = array( 'navigation-studio.php', 'uninstall.php', 'readme.txt', 'README.md', 'LICENSE', 'includes', 'build', 'languages' );
$files = array();
foreach ( $paths as $path ) {
	$absolute = $root . '/' . $path;
	if ( is_file( $absolute ) ) {
		$files[] = $absolute;
		continue;
	}
	if ( ! is_dir( $absolute ) ) {
		continue;
	}
	$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $absolute, FilesystemIterator::SKIP_DOTS ) );
	foreach ( $iterator as $file ) {
		if ( $file->isFile() ) {
			$files[] = $file->getPathname();
		}
	}
}
sort( $files, SORT_STRING );
foreach ( $files as $file ) {
	$relative = 'navigation-studio/' . str_replace( '\\', '/', substr( $file, strlen( $root ) + 1 ) );
	$zip->addFile( $file, $relative );
	if ( method_exists( $zip, 'setMtimeName' ) ) {
		$zip->setMtimeName( $relative, 1735689600 );
	}
}
$zip->close();
echo $target . PHP_EOL;
