function showBalloon() {
	var msg = 'Did you know you can track your vital stats and glucose levels?<br/>Select My Vitals Stats, then "Track vital stats" or "Track glucose readings".'
	$('#myvitalstats').attr('title',msg);
	$('#myvitalstats').showBalloon({
		position: "bottom",
		showDuration: 200,
		hideDuration: 200,
		css: {
			border: 'solid 1px #A42732',
			padding: '10px',
			fontSize: '14px',
			fontWeight: 'bold',
			lineHeight: '1.5',
			backgroundColor: '#F4E0E1',
			color: '#A42732',
			opacity: "1",
		}
	});
	//$('#myvitalstats').showBalloon();
	setTimeout(function() {
		$('#myvitalstats').hideBalloon();
	}, 10000);
}

$(document).ready(function() {
	setTimeout(showBalloon, 2000);
});
