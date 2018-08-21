<?php

/*
 * Copyright 2018 Anthony Calabretta
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace AryToNeX\RGBDuino;

class Utils{

	// Color sanitizer for LED strip
	public static function sanitizeColor(array $rgb, float $minSaturation = 0.75, float $minLuminance = 0.50) : array{
		if ($rgb["r"] == $rgb["g"] && $rgb["g"] == $rgb["b"]) if ($rgb["r"] > 64)
		    return array("r" => 255, "g" => 255, "b" => 255);
		else
		    return array("r" => 0, "g" => 0, "b" => 0);

		$hsv = color\Color::fromRgbToHsv($rgb);

		$hsv["s"] = ($hsv["s"] < $minSaturation ? $minSaturation : $hsv["s"]);
		$hsv["v"] = ($hsv["v"] < $minLuminance ? $minLuminance : $hsv["v"]);

		return color\Color::fromHsvToRgb($hsv);
	}


	// Image utils
    /** @deprecated */
	public static function dominantColorFromImageLegacy(string $url) : array{
		$im = file_get_contents($url);
		$rTotal = 0;
		$gTotal = 0;
		$bTotal = 0;
		$total = 0;

		$i = imagecreatefromstring($im);
		for ($x = 0; $x < imagesx($i); $x++) {
			for ($y = 0; $y < imagesy($i); $y++) {
				$rgb = imagecolorat($i, $x, $y);
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;
				$rTotal += $r;
				$gTotal += $g;
				$bTotal += $b;
				$total++;
			}
		}
		$rAverage = (int) round($rTotal / $total);
		$gAverage = (int) round($gTotal / $total);
		$bAverage = (int) round($bTotal / $total);

		return array("r" => $rAverage, "g" => $gAverage, "b" => $bAverage);
	}

	public static function dominantColorFromImage(string $url) : array{
        $palette = color\Palette::fromFilename($url);
        $extractor = new color\ColorExtractor($palette);
        return color\Color::fromIntToRgb($extractor->extract(1)[0]);
    }

    public static function dominantColorArrayFromImage(string $url, int $colors = 5) : array{
        $palette = color\Palette::fromFilename($url);
        $extractor = new color\ColorExtractor($palette);
        $colorsArr = $extractor->extract($colors);
        for($i = 0; $i < count($colorsArr); $i++)
            $colorsArr[$i] = color\Color::fromIntToRgb($colorsArr[$i]);
        return $colorsArr;
    }


	// Desktop environment utils (Thanks to TDesktop)
    public static function getDesktopEnvironment(){
        $xdgCurrentDesktop = strtolower(getenv("XDG_CURRENT_DESKTOP"));
        $list = explode(":", $xdgCurrentDesktop);
        $desktopSession = strtolower(getenv("DESKTOP_SESSION"));
        $kdeSession = getenv("KDE_SESSION_VERSION");
        if (!empty($list)) {
            if (in_array("unity", $list)) {
                // gnome-fallback sessions set XDG_CURRENT_DESKTOP to Unity
                // DESKTOP_SESSION can be gnome-fallback or gnome-fallback-compiz
                if (strpos($desktopSession, "gnome-fallback") >= 0) {
                    return "Gnome";
                }
                return "Unity";
            } else if (in_array("xfce", $list)) {
                return "XFCE";
            } else if (in_array("pantheon", $list)) {
                return "Pantheon";
            } else if (in_array("gnome", $list)) {
                if (in_array("ubuntu", $list))
                    return "Ubuntu";
                return "Gnome";
            } else if (in_array("kde", $list)) {
                if ($kdeSession == "5") {
                    return "KDE5";
                }
                return "KDE4";
            }
        }

        if ($desktopSession !== "") {
            if ($desktopSession == "gnome" || $desktopSession == "mate") {
                return "Gnome";
            } else if ($desktopSession == "kde4" || $desktopSession == "kde-plasma") {
                return "KDE4";
            } else if ($desktopSession == "kde") {
                // This may mean KDE4 on newer systems, so we have to check.
                if ($kdeSession !== "") {
                    return "KDE4";
                }
                return "KDE3";
            } else if (strpos($desktopSession, "xfce") >= 0 || $desktopSession == "xubuntu") {
                return "XFCE";
            } else if ($desktopSession == "awesome") {
                return "Awesome";
            }
        }

        // Fall back on some older environment variables.
        // Useful particularly in the DESKTOP_SESSION=default case.
        if (getenv("GNOME_DESKTOP_SESSION_ID") !== "") {
            return "Gnome";
        } else if (getenv("KDE_FULL_SESSION") !== "") {
            if ($kdeSession !== "") {
                return "KDE4";
            }
            return "KDE3";
        }

        return "Other";
    }


    // Wallpaper utils
    public static function getWallpaperURL(){
        $env = self::getDesktopEnvironment();
        switch($env){
            default:
                echo "Unsupported DE $env!";
                return "";
            case "XFCE":
                return exec("xfconf-query -c xfce4-desktop -p /backdrop/screen0/monitor0/workspace0/last-image");
            case "Gnome":
            case "Unity":
            case "Pantheon":
                return exec("gsettings get org.gnome.desktop.background picture-uri");
            case "KDE5":
                // command by @LucentW
                return exec("grep /home/$(whoami)/.kde/share/config/plasma-overlay-appletsrc 'wallpaper=' | sed 's/wallpaper=//'");
        }
    }

}