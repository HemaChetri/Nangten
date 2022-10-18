<?php
/*
 * Helper -- Leaflet (Get Leaflet Map)
 * chophel@athang.com
 */
namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Interop\Container\ContainerInterface;

class LeafletHelper extends AbstractHelper
{
	private $_container;
	public function __construct(ContainerInterface $container)
    {
        $this->_container = $container;
    }
	
	public function __invoke($map_id, $districts, $locations, $activities, $basePath, $dashboard)
	{
		$districtList = '';
		foreach($districts as $District):
			$districtList .= "{'type':'Feature','properties': {dName: '".ucfirst(strtolower($District['DzongkhagNameEn']))."', status: '".$District['status']."'}, 'geometry': { 'type': 'Polygon', 'coordinates':".$District['coordinates']."},},";
		endforeach;

		$locationList = '';
		$markerList = '';
		$marker = 1; foreach($locations as $Location): if(!empty($Location['coordinates']) && strpos($Location['coordinates'], ',')):
			switch($Location['location_type']):
				case 1: $Icon = 'blueIcon'; break;
				case 2: $Icon = 'orangeIcon'; break;
				case 3: $Icon = 'yellowIcon'; break;
				case 4: $Icon = 'violetIcon'; break;
				default: $Icon = 'blueIcon'; break;
			endswitch;
			$locationList .= "var marker".$marker." = L.marker([".$Location['coordinates']."], {icon: ".$Icon."}).bindPopup('<table><tr><th>PIU : </th><td>".$Location['location']."</td></tr><tr><th>PIU Code: </th><td>".$Location['location_code']."</td></tr><tr><th>PIU Type: </th><td>".$Location['location_type_name']."</td></tr></table>');";
			$markerList .= 'marker'.$marker++.",";
		endif; endforeach;
		
		$activityList = '';
		$actmarkerList = '';
		$ActivityCoordinates = '';
		$actmarker = 1; if(sizeof($activities)>0):foreach($activities as $Activity): if(!empty($Activity['coordinates']) && strpos($Activity['coordinates'], ',')):
			$activityList .= "var actmarker".$actmarker." = L.marker([".$Activity['coordinates']."], {icon: redIcon}).bindPopup('<table><tr><th>Impl No : </th><td>".$Activity['implementation_no']."</td></tr></table>');";
			$actmarkerList .= 'actmarker'.$actmarker++.",";
			$ActivityCoordinates = $Activity['coordinates'];
		endif; endforeach; endif;
		
		if($dashboard == '1'):
			$overlayMaps = "var overlayMaps = {'PIU': Locations,'Activities': Activities,};";
			$options = "var options = {center: [27.477173, 90.422339],zoomSnap: 0.2,zoom: 7.6,layers: [googleSatellite, Locations, Activities],fullscreenControl: true,};";
		else:
			$overlayMaps = "var overlayMaps = {'PIU': Locations,'Activities': Activities,};";
			if(sizeof($activities)>0):
				$options = "var options = {center: [".$ActivityCoordinates."],zoom: 17,layers: [googleSatellite, Activities],fullscreenControl: true,};";
			else:
				$options = "var options = {center: [27.477173, 90.422339],zoomSnap: 0.2,zoom: 7.6,layers: [googleSatellite, Activities],fullscreenControl: true,};";
			endif;
		endif;
		
		echo <<<EOF
		<script>
			var Districts = [$districtList];
			
			var googleSatellite = L.tileLayer('http://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}',{
				maxZoom: 35,
				minZoom: 6,
				subdomains:['mt0','mt1','mt2','mt3'],
				attribution: '&copy; Google Satellite'
			});
			var googleStreets = L.tileLayer('http://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}',{
				maxZoom: 35,
				minZoom: 6,
				subdomains:['mt0','mt1','mt2','mt3'],
				attribution: '&copy; Google Streets'
			});
			var googleHybrid = L.tileLayer('http://{s}.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}',{
				maxZoom: 35,
				minZoom: 6,
				subdomains:['mt0','mt1','mt2','mt3'],
				attribution: '&copy; Google Hybrid'
			});
			var googleTerrain = L.tileLayer('http://{s}.google.com/vt/lyrs=p&x={x}&y={y}&z={z}',{
				maxZoom: 35,
				minZoom: 6,
				subdomains:['mt0','mt1','mt2','mt3'],
				attribution: '&copy; Google Terrain'
			});
			var baseMaps = {
				"Google Satellite": googleSatellite,
				"Google Street": googleStreets,
				"Google Hybrid": googleHybrid,
				"Google Terrain": googleTerrain,
			};

			var LeafIcon = L.Icon.extend({
				options: {
					shadowUrl: "$basePath/leaflet/images/marker-shadow.png",
					iconSize: [25, 41],
					iconAnchor: [12, 41],
					popupAnchor: [1, -34],
					shadowSize: [41, 41]
				}
			});

			var blueIcon = new LeafIcon({iconUrl: "$basePath/leaflet/images/marker-icon-2x-blue.png"}),
				goldIcon = new LeafIcon({iconUrl: "$basePath/leaflet/images/marker-icon-2x-gold.png"}),
				redIcon = new LeafIcon({iconUrl: "$basePath/leaflet/images/marker-icon-2x-red.png"}),
				greenIcon = new LeafIcon({iconUrl: "$basePath/leaflet/images/marker-icon-2x-green.png"}),
				orangeIcon = new LeafIcon({iconUrl: "$basePath/leaflet/images/marker-icon-2x-orange.png"}),
				yellowIcon = new LeafIcon({iconUrl: "$basePath/leaflet/images/marker-icon-2x-yellow.png"}),
				violetIcon = new LeafIcon({iconUrl: "$basePath/leaflet/images/marker-icon-2x-violet.png"}),
				greyIcon = new LeafIcon({iconUrl: "$basePath/leaflet/images/marker-icon-2x-grey.png"}),
				blackIcon = new LeafIcon({iconUrl: "$basePath/leaflet/images/marker-icon-2x-black.png"});

			$locationList
			var Locations = L.layerGroup([$markerList]);
			$activityList
			var Activities = L.layerGroup([$actmarkerList]);
			
			$overlayMaps
			
			$options
			
			var $map_id = L.map('$map_id', options);
			L.control.layers(baseMaps, overlayMaps).addTo($map_id);

			L.geoJSON(Districts, {
				onEachFeature: function (feature, layer) {
					layer.bindTooltip(feature.properties.dName, {permanent: true, direction: 'center', className: 'leaflet-tooltip-own'});
				},
				style: function(feature) {
					switch (feature.properties.status) {
						case '1': return {color: "#d63939",'weight': 2,'opacity': 1,'fill': '#fff','fillOpacity': 0};
						case '0':   return {color: "#206bc4",'weight': 1,'opacity': 1,'fill': '#fff','fillOpacity': 0};
					}
				}
			}).addTo($map_id);
		</script>
		EOF;
	}
}