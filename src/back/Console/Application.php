<?php

/*
 * This file is part of the Sterlett project <https://github.com/sterlett/sterlett>.
 *
 * (c) 2020 Pavel Petrov <itnelo@gmail.com>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license https://opensource.org/licenses/GPL-3.0 GPL-3.0
 */

declare(strict_types=1);

namespace Sterlett\Console;

use Symfony\Component\Console\Application as BaseApplication;

/**
 * Provides a console interface to the internal services through commands
 */
final class Application extends BaseApplication
{
    /**
     * {@inheritDoc}
     */
    public function getHelp()
    {
        $helpMessage = "\n";

        $helpMessage .= <<<ASCII
;C0tf;.
LGCGCLCf;.
itii;:itLLfffftLLffLL1;,
 ii:;;::;1tLCCL◖◗LfCCLLf1;,
  ii:;;:;:;;11fLLffLLfLCLff1,
   i;:::;:::ii111ttffffLCLftf1.
   ,..i;:::;1iiiiii1i1tfLfft1ft.
      ,;;;i11    ii1i111t1tf11fiii:.
      .,iftt111111tt111iii;tft1tttft:
   ,itLfi;i1tffftt111111111tGGCLt11tti.
.ifL1:,      .:1fLfffttttft1i1LCCCCfttti
tf;             .;1tfLLLLfft11it;;fLLLfLi
                     .,itLfffttt    ,itft
                     .i11tfft11tt,     ti.
ASCII;

        $helpMessage .= "\n";

        $helpMessage .= parent::getHelp();

        return $helpMessage;
    }
}
