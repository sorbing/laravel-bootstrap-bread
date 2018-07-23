<?php

namespace Sorbing\Bread\Services;

class BreadService
{
    /**
     * Render a Blade template from string with data
     * @param $string
     * @param array $data
     * @return string
     * @throws \Symfony\Component\Debug\Exception\FatalThrowableError
     */
    public static function renderBlade($string, array $data = [])
    {
        $php = \Blade::compileString($string);

        $obLevel = ob_get_level();
        ob_start();
        extract($data, EXTR_SKIP);

        try {
            eval('?' . '>' . $php);
        } catch (\Exception $e) {
            while (ob_get_level() > $obLevel) ob_end_clean();
            throw $e;
        } catch (\Throwable $e) {
            while (ob_get_level() > $obLevel) ob_end_clean();
            throw new \Symfony\Component\Debug\Exception\FatalThrowableError($e);
        }

        return ob_get_clean();
    }
}