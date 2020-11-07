<?php defined('BASEPATH') OR exit('No direct script access allowed.');

include(APPPATH . '/libraries/Parsedown.php');

/**
 *  Parsedown
 *  http://parsedown.org
 *  (c) Emanuil Rusev
 *  http://erusev.com
 *
 *  For the full license information, view the LICENSE file that was distributed
 *  with this source code.
 */
class Parsedown_toc extends Parsedown {

	protected $TOC = array();
    protected $SupRegex = array(
        '+' => '/^-((?:\\\\+|[^+]|++[^+]*++)+?)+(?!_)\b/us'
    );
    protected $SubRegex = array(
        '-' => '/^-((?:\\\\-|[^-]|--[^-]*--)+?)-(?!_)\b/us'
    );

	public function __construct()
	{

		$this->BlockTypes['Y'] = array('YouTube');
		$this->InlineTypes['+'] = array('Sup');
		$this->InlineTypes['-'] = array('Sub');
		$this->InlineTypes['@'] = array('Small');
	}

    public function text($text)
    {

		$this->TOC = array();

		$output = parent::text($text);
    //  return parent::text($text);
      	return str_replace('[TOC]', $this->TOC(), $output);
    }

    protected function blockYouTube($Line)
    {

        if (preg_match('/^YT[ ]?(.*)/', $Line['text'], $matches)) {

            $Block = array(
                'element' => array(
                    'name' => 'iframe',
                    'handler' => 'lines',
                    'text' => array(),
                    'attributes' => array(
                        'src' => 'https://www.youtube.com/embed/' . (string)$matches[1],
                        'width' => 600,
                        'height' => 400,
                        'allowfullscreen'=> 'allowfullscreen'
                    )
                )
            );

            return $Block;
        }
    }

    protected function inlineSup($Excerpt)
    {

        if (!isset($Excerpt['text'][1])) {

            return;
        }

        $marker = $Excerpt['text'][0];
        if (!preg_match($this->SupRegex[$marker], $Excerpt['text'], $matches)) {

            return;
        }

        return array(
            'extent' => strlen($matches[0]),
            'element' => array(
                'name' => 'sup',
                'handler' => 'line',
                'text' => $matches[1],
            ),
        );
    }

    protected function inlineSub($Excerpt)
    {

        if (!isset($Excerpt['text'][1])) {

            return;
        }

        $marker = $Excerpt['text'][0];
        if (!preg_match($this->SubRegex[$marker], $Excerpt['text'], $matches)) {

            return;
        }

        return array(
            'extent' => strlen($matches[0]),
            'element' => array(
                'name' => 'sub',
                'handler' => 'line',
                'text' => $matches[1],
            ),
        );
    }

    protected function blockHeader($Line)
    {

        if (isset($Line['text'][1])) {

            $level = 1;
            while (isset($Line['text'][$level]) and $Line['text'][$level] === '#') {

                $level ++;
            }

            if ($level > 6) {

                return;
            }

            $this->TOC[] = array($level, $Line['text']);

            $text = trim($Line['text'], '# ');
            $Block = array(
                'element' => array(
                    'name' => 'h' . min(6, $level),
                    'text' => $text,
                    'handler' => 'line',
                    'attributes' => array(
                    	'id' => 'h_' . count($this->TOC)
                    )
                ),
            );
            return $Block;
        }
    }

    protected function TOC()
    {

     	$output = '';
     	foreach ($this->TOC as $i => $item) {

            if ($item[0] < 3) continue;

     		if (!isset($levelPrev) || $item[0] > $levelPrev) {

     			$output .= '<ul>';
     		}

     		if (isset($levelPrev) && $item[0] < $levelPrev) {

     			$output .= '</ul>';
     		}

     		$output .= '<li><a href="#h_' . ($i + 1) . '">' . trim($item[1], '# ') . '</a>';

     		$levelPrev = $item[0];
     	}
    	return '<h3>Table of contents</h3>' . $output . '</ul>';
    }

    protected function inlineLink($Excerpt)
    {

        $Element = array(
            'name' => 'a',
            'handler' => 'line',
            'text' => null,
            'attributes' => array(
                'href' => null,
                'title' => null,
            ),
        );

        $extent = 0;
        $remainder = $Excerpt['text'];
        if (preg_match('/\[((?:[^][]|(?R))*)\]/', $remainder, $matches)) {

            $Element['text'] = $matches[1];
            $extent += strlen($matches[0]);
            $remainder = substr($remainder, $extent);
        }
        else {

            return;
        }

        if (preg_match('/^[(]((?:[^ ()]|[(][^ )]+[)])+)(?:[ ]+("[^"]*"|\'[^\']*\'))?[)]/', $remainder, $matches)) {

            $Element['attributes']['href'] = $matches[1];
            if (isset($matches[2])) {

                $Element['attributes']['title'] = substr($matches[2], 1, - 1);
            }
            $extent += strlen($matches[0]);
        }
        else {

            if (preg_match('/^\s*\[(.*?)\]/', $remainder, $matches)) {

                $definition = strlen($matches[1]) ? $matches[1] : $Element['text'];
                $definition = strtolower($definition);
                $extent += strlen($matches[0]);
            }
            else {

                $definition = strtolower($Element['text']);
            }

            if (!isset($this->DefinitionData['Reference'][$definition])) {

                return;
            }

            $Definition = $this->DefinitionData['Reference'][$definition];
            $Element['attributes']['href'] = $Definition['url'];
            $Element['attributes']['title'] = $Definition['title'];
        }
        $Element['attributes']['target'] = '_parent';

        if (substr($Element['attributes']['href'], 0, 6) == '#blog_') {

            $Element['attributes']['class'] = '_blog';
            $Element['attributes']['data-hash'] = substr($Element['attributes']['href'], 6);
        }
        elseif (substr($Element['attributes']['href'], 0, 8) == '#contact') {

            $Element['attributes']['class'] = '_contact';
            $Element['attributes']['data-hash'] = substr($Element['attributes']['href'], 1);
        }
        elseif (substr($Element['attributes']['href'], 0, 6) == '#snake') {

            $Element['attributes']['class'] = '_snake';
            $Element['attributes']['data-hash'] = substr($Element['attributes']['href'], 1);
        }
        elseif (substr($Element['attributes']['href'], 0, 9) == '#gallery_') {

        	$Element['attributes']['class'] = '_gallery';
        	$Element['attributes']['data-hash'] = substr($Element['attributes']['href'], 9);
        	$Element['attributes']['href'] = substr($Element['attributes']['href'], 9);
        }
        elseif (substr($Element['attributes']['href'], 0, 4) == '#db_') {

            $Element['attributes']['class'] = '_database';
            $Element['attributes']['data-hash'] = substr($Element['attributes']['href'], 4);
        }
        elseif (substr($Element['attributes']['href'], 0, 8) == '#site_') {

            $Element['attributes']['class'] = '_site';
            $Element['attributes']['data-hash'] = substr($Element['attributes']['href'], 8);
        }
        elseif (substr($Element['attributes']['href'], 0, 8) == '#tracks_') {

            $Element['attributes']['class'] = '_track';
            $Element['attributes']['data-hash'] = substr($Element['attributes']['href'], 8);
        }


        $Element['attributes']['href'] = str_replace(array('&', '<'), array('&amp;', '&lt;'), $Element['attributes']['href']);
        return array(
            'extent' => $extent,
            'element' => $Element,
        );
    }
}