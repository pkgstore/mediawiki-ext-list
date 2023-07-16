<?php

namespace MediaWiki\Extension\PkgStore;

use MWException;
use Parser, OutputPage, Skin;

/**
 * Class MW_EXT_List
 */
class MW_EXT_List
{
  /**
   * Get JSON data.
   *
   * @param $type
   *
   * @return array
   */
  private static function getList($type): array
  {
    $get = MW_EXT_Kernel::getJSON(__DIR__ . '/store/list.json');
    return $get['list'][$type] ?? [] ?: [];
  }

  /**
   * Register tag function.
   *
   * @param Parser $parser
   *
   * @return void
   * @throws MWException
   */
  public static function onParserFirstCallInit(Parser $parser): void
  {
    $parser->setFunctionHook('list', [__CLASS__, 'onRenderTag']);
  }

  /**
   * Render tag function.
   *
   * @param Parser $parser
   * @param string $type
   * @param string $style
   *
   * @return string|null
   */
  public static function onRenderTag(Parser $parser, string $type = '', string $style = ''): ?string
  {
    // Argument: type.
    $getType = MW_EXT_Kernel::outClear($type ?? '' ?: '');
    $outType = MW_EXT_Kernel::outNormalize($getType);

    // Check license type, set error category.
    if (!self::getList($outType)) {
      $parser->addTrackingCategory('mw-list-error-category');

      return null;
    }

    // Argument: style.
    $getStyle = MW_EXT_Kernel::outClear($style ?? '' ?: '');
    $outStyle = MW_EXT_Kernel::outNormalize($getStyle);

    // Get data.
    $getList = self::getList($outType);

    // Sort data.
    asort($getList);

    // Build style class.
    switch ($outStyle) {
      case 'grid':
        $outClass = 'grid';
        break;
      case 'list':
        $outClass = 'list';
        break;
      case 'inline':
        $outClass = 'inline';
        break;
      default:
        $parser->addTrackingCategory('mw-list-error-category');

        return null;
    }

    // Set URL item.
    $outItem = '';

    foreach ($getList as $item) {
      $title = empty($item['title']) ? '' : $item['title'];
      $icon = empty($item['icon']) ? 'fas fa-globe' : $item['icon'];
      $bg_color = empty($item['style']['background-color']) ? '' : 'background-color:' . $item['style']['background-color'] . ';';
      $color = empty($item['style']['color']) ? '' : 'color:' . $item['style']['color'] . ';';
      $href = empty($item['url']) ? '' : $item['url'];
      $desc = MW_EXT_Kernel::getMessageText('list', $item['description']);

      if ($outClass === 'grid') {
        $outItem .= '<div>';
        $outItem .= '<div><a style="' . $bg_color . $color . '" title="' . $desc . '" href="' . $href . '" target="_blank"><i class="' . $icon . '"></i></a></div>';
        $outItem .= '<div><h4><a href="' . $href . '" target="_blank">' . $title . '</a></h4><div>' . $desc . '</div></div>';
        $outItem .= '</div>';
      } else if ($outClass === 'list') {
        $outItem .= '<li><a title="' . $desc . '" href="' . $href . '" target="_blank">' . $title . '</a></li>';
      }
    }

    // Out HTML.
    $outHTML = '<div class="mw-list mw-list-' . $outClass . '">';

    if ($outClass === 'grid') {
      $outHTML .= '<div class="mw-list-body"><div class="mw-list-content">' . $outItem . '</div></div>';

    } else if ($outClass === 'list') {
      $outHTML .= '<div class="mw-list-body"><ul class="mw-list-content">' . $outItem . '</ul></div>';
    }

    $outHTML .= '</div>';

    // Out parser.
    return $parser->insertStripItem($outHTML, $parser->getStripState());
  }

  /**
   * Load resource function.
   *
   * @param OutputPage $out
   * @param Skin $skin
   *
   * @return void
   */
  public static function onBeforePageDisplay(OutputPage $out, Skin $skin): void
  {
    $out->addModuleStyles(['ext.mw.list.styles']);
  }
}
