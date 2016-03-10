<?php
namespace watoki\curir\protocol;

class MimeTypes {

    private static $map = array(
        'x-world/x-3dmf' => array('3dmf', '3dm', 'qd3d', 'qd3'),
        'application/postscript' => array('ai', 'eps', 'ps'),
        'audio/x-aiff' => array('aif', 'aiff', 'aifc'),
        'application/astound' => array('asd', 'asn'),
        'audio/basic' => array('au', 'snd'),
        'video/x-msvideo' => array('avi'),
        'application/x-bcpio' => array('bcpio'),
        'application/x-macbinary' => array('bin'),
        'application/octet-stream' => array('bin', 'exe', 'com', 'dll', 'class'),
        'image/cis-cod' => array('cod'),
        'application/x-cpio' => array('cpio'),
        'application/x-csh' => array('csh'),
        'text/css' => array('css'),
        'text/comma-separated-values' => array('csv'),
        'application/x-director' => array('dcr', 'dir', 'dxr'),
        'application/msword' => array('doc', 'dot'),
        'audio/x-dspeeh' => array('dus', 'cht'),
        'application/x-dvi' => array('dvi'),
        'drawing/x-dwf' => array('dwf'),
        'application/acad' => array('dwg'),
        'application/dxf' => array('dxf'),
        'audio/echospeech' => array('es'),
        'text/x-setext' => array('etx'),
        'application/x-envoy' => array('evy'),
        'image/x-freehand' => array('fh4', 'fh5', 'fhc'),
        'image/fif' => array('fif'),
        'image/gif' => array('gif'),
        'application/x-gtar' => array('gtar'),
        'application/gzip' => array('gz'),
        'application/x-hdf' => array('hdf'),
        'application/mshelp' => array('hlp', 'chm'),
        'application/mac-binhex40' => array('hqx'),
        'application/json' => array('json'),
        'text/html' => array('htm', 'html', 'shtml'),
        'application/xhtml+xml' => array('htm', 'html', 'shtml', 'xhtml'),
        'image/x-icon' => array('ico'),
        'image/ief' => array('ief'),
        'image/jpeg' => array('jpeg', 'jpg', 'jpe'),
        'application/javascript' => array('js'),
        'application/x-javascript' => array('js'),
        'text/javascript' => array('js'),
        'application/x-latex' => array('latex'),
        'application/x-troff-man' => array('man', 'troff'),
        'application/mbedlet' => array('mbd'),
        'image/vasa' => array('mcf'),
        'application/x-troff-me' => array('me', 'troff'),
        'application/x-troff-ms' => array('me', 'troff'),
        'audio/x-midi' => array('mid', 'midi'),
        'application/mif' => array('mif'),
        'application/x-mif' => array('mif'),
        'video/x-sgi-movie' => array('movie'),
        'audio/x-mpeg' => array('mp2'),
        'video/mpeg' => array('mpeg', 'mpg', 'mpe'),
        'application/x-netcdf' => array('nc', 'cdf'),
        'application/x-nschat' => array('nsc'),
        'application/oda' => array('oda'),
        'image/x-portable-bitmap' => array('pbm'),
        'application/pdf' => array('pdf'),
        'image/x-portable-graymap' => array('pgm'),
        'application/x-httpd-php' => array('php', 'phtml'),
        'image/png' => array('png'),
        'image/x-portable-anymap' => array('pnm'),
        'image/x-portable-pixmap' => array('ppm'),
        'application/mspowerpoint' => array('ppt', 'ppz', 'pps', 'pot'),
        'application/listenup' => array('ptlk'),
        'video/quicktime' => array('qt', 'mov'),
        'audio/x-pn-realaudio' => array('ram', 'ra'),
        'image/cmu-raster' => array('ras'),
        'image/x-rgb' => array('rgb'),
        'audio/x-pn-realaudio-plugin' => array('rpm'),
        'application/rtc' => array('rtc'),
        'application/rtf' => array('rtf'),
        'text/rtf' => array('rtf'),
        'text/richtext' => array('rtx'),
        'application/x-supercard' => array('sca'),
        'text/x-sgml' => array('sgm', 'sgml'),
        'application/x-sh' => array('sh'),
        'application/x-shar' => array('shar'),
        'application/x-stuffit' => array('sit'),
        'application/studiom' => array('smp'),
        'application/futuresplash' => array('spl'),
        'application/x-sprite' => array('spr', 'sprite'),
        'application/x-wais-source' => array('src'),
        'audio/x-qt-stream' => array('stream'),
        'application/x-sv4cpio' => array('sv4cpio'),
        'application/x-sv4crc' => array('sv4crc'),
        'application/x-shockwave-flash' => array('swf', 'cab'),
        'application/x-troff' => array('t', 'tr', 'roff'),
        'text/x-speech' => array('talk', 'spc'),
        'application/x-tar' => array('tar'),
        'application/toolbook' => array('tbk'),
        'application/x-tcl' => array('tcl'),
        'application/x-tex' => array('tex'),
        'application/x-texinfo' => array('texinfo', 'texi'),
        'image/tiff' => array('tiff', 'tif'),
        'audio/tsplayer' => array('tsi'),
        'application/dsptype' => array('tsp'),
        'text/tab-separated-values' => array('tsv'),
        'text/plain' => array('txt'),
        'application/x-ustar' => array('ustar'),
        'video/vnd.vivo' => array('viv', 'vivo'),
        'application/vocaltec-media-desc' => array('vmd'),
        'application/vocaltec-media-file' => array('vmf'),
        'audio/voxware' => array('vox'),
        'workbook/formulaone' => array('vts', 'vtts'),
        'audio/x-wav' => array('wav'),
        'image/vnd.wap.wbmp' => array('wbmp'),
        'text/vnd.wap.wml' => array('wml'),
        'application/vnd.wap.wmlc' => array('wmlc'),
        'text/vnd.wap.wmlscript' => array('wmls'),
        'application/vnd.wap.wmlscriptc' => array('wmlsc'),
        'model/vrml' => array('wrl'),
        'x-world/x-vrml' => array('wrl'),
        'image/x-xbitmap' => array('xbm'),
        'application/msexcel' => array('xls', 'xla'),
        'application/xml' => array('xml'),
        'text/xml' => array('xml'),
        'image/x-xpixmap' => array('xpm'),
        'image/x-windowdump' => array('xwd'),
        'application/x-compress' => array('z'),
        'application/zip' => array('zip')
    );

    /**
     * Retruns MIME type belonging to given file extension or null if not found.
     *
     * @static
     * @param $extension
     * @return null|string
     */
    public static function getType($extension) {
        foreach (self::$map as $mime => $exts) {
            if (in_array(strtolower($extension), $exts)) {
                return $mime;
            }
        }
        return null;
    }

    public static function getExtensions($type) {
        if (!array_key_exists($type, self::$map)) {
            return array();
        }
        return self::$map[$type];
    }

}
