function ScaleSlider(){var $=jssor_slider1.$Elmt.parentNode.clientWidth;$?jssor_slider1.$ScaleWidth(Math.max(Math.min($,800),300)):window.setTimeout(ScaleSlider,30)}var _SlideshowTransitions=[{$Duration:1200,x:.3,$During:{$Left:[.3,.7]},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2},{$Duration:1200,x:-.3,$SlideOut:!0,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2},{$Duration:1200,x:-.3,$During:{$Left:[.3,.7]},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2},{$Duration:1200,x:.3,$SlideOut:!0,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2},{$Duration:1200,y:.3,$During:{$Top:[.3,.7]},$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Outside:!0},{$Duration:1200,y:-.3,$SlideOut:!0,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Outside:!0},{$Duration:1200,y:-.3,$During:{$Top:[.3,.7]},$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2},{$Duration:1200,y:.3,$SlideOut:!0,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2},{$Duration:1200,x:.3,$Cols:2,$During:{$Left:[.3,.7]},$ChessMode:{$Column:3},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Outside:!0},{$Duration:1200,x:.3,$Cols:2,$SlideOut:!0,$ChessMode:{$Column:3},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Outside:!0},{$Duration:1200,y:.3,$Rows:2,$During:{$Top:[.3,.7]},$ChessMode:{$Row:12},$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2},{$Duration:1200,y:.3,$Rows:2,$SlideOut:!0,$ChessMode:{$Row:12},$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2},{$Duration:1200,y:.3,$Cols:2,$During:{$Top:[.3,.7]},$ChessMode:{$Column:12},$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Outside:!0},{$Duration:1200,y:-.3,$Cols:2,$SlideOut:!0,$ChessMode:{$Column:12},$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2},{$Duration:1200,x:.3,$Rows:2,$During:{$Left:[.3,.7]},$ChessMode:{$Row:3},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Outside:!0},{$Duration:1200,x:-.3,$Rows:2,$SlideOut:!0,$ChessMode:{$Row:3},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2},{$Duration:1200,x:.3,y:.3,$Cols:2,$Rows:2,$During:{$Left:[.3,.7],$Top:[.3,.7]},$ChessMode:{$Column:3,$Row:12},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Outside:!0},{$Duration:1200,x:.3,y:.3,$Cols:2,$Rows:2,$During:{$Left:[.3,.7],$Top:[.3,.7]},$SlideOut:!0,$ChessMode:{$Column:3,$Row:12},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Outside:!0},{$Duration:1200,$Delay:20,$Clip:3,$Assembly:260,$Easing:{$Clip:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2},{$Duration:1200,$Delay:20,$Clip:3,$SlideOut:!0,$Assembly:260,$Easing:{$Clip:$JssorEasing$.$EaseOutCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2},{$Duration:1200,$Delay:20,$Clip:12,$Assembly:260,$Easing:{$Clip:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2},{$Duration:1200,$Delay:20,$Clip:12,$SlideOut:!0,$Assembly:260,$Easing:{$Clip:$JssorEasing$.$EaseOutCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}],options={$FillMode:1,$AutoPlay:!0,$AutoPlayInterval:1500,$PauseOnHover:1,$DragOrientation:3,$ArrowKeyNavigation:!0,$SlideDuration:800,$SlideshowOptions:{$Class:$JssorSlideshowRunner$,$Transitions:_SlideshowTransitions,$TransitionsOrder:1,$ShowLink:!0},$ArrowNavigatorOptions:{$Class:$JssorArrowNavigator$,$ChanceToShow:1},$ThumbnailNavigatorOptions:{$Class:$JssorThumbnailNavigator$,$ChanceToShow:2,$ActionMode:1,$SpacingX:8,$DisplayPieces:10,$ParkingPosition:360}},jssor_slider1=new $JssorSlider$("slider1_container",options);ScaleSlider(),jQuery(window).bind("load",ScaleSlider),jQuery(window).bind("resize",ScaleSlider),jQuery(window).bind("orientationchange",ScaleSlider);