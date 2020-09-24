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
.tt0Gf;,                                                 
f8808@8GC1,                                              
tfffft1ifCGLi, ...,,:,,. ..,:.                           
;f1i1i;::i1fLCLLCCLftG0GCCCGCf1i,.                       
 it;::;;::;i11tLCCCL◖◗tLLfLGCLLLfti:                     
  ;t;:::;;;;;;;i1ffLCCLfftfCffLLLfft1i,                 
   :1;;;;::;;:;:;ii1ttLffLfCLfLLCCLLfffi.                
    ;i;::,:;::::;1ii11111tffffffLCLLfttffi.              
    ::. ;;;:::::;1iiiiiiii1ti1tffCLfftt1tLt.             
        :1;;:          ;;iiiiii11tfffffttttL:...          
         ,;iii        i;iii111111111i1ff11tt1ttt1;.       
          .,tft111111i11tttt1111iiii;itt111ttttfft;      
      .;tLLt1tttftfttttttt11111111iii1LGGLtttt111tti.    
   .ifCLf1;,. .,:;1tfffftt11111111tttttfCG00Lfttttttti.  
.iLGL1:.            ,itLLLfffffttffft11i;itLCGGGLftttffi 
fGf:                   ,itfLLLLLLLfffft11111t:iLCGCLfttf:
..                         .,:i1tffLLffffttt;   .,ifCGCLf
                                :;tffffftttfi        ,;ti
                              ;fftt1tfttt11tff:
ASCII;

        $helpMessage .= "\n";

        $helpMessage .= parent::getHelp();

        return $helpMessage;
    }
}
