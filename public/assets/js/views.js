// Deprecated
function ViewClaim(id)
{
	document.frmDetail.id.value = id;
	document.frmDetail.action = "claim-detail.php";
	document.frmDetail.submit();
	return false;
}

// Deprecated
function ViewReferral(id)
{
	document.frmDetail.id.value = id;
	document.frmDetail.action = "referral-detail.php";
	document.frmDetail.submit();
	return false;
}

function ViewLab(id, order)
{
	document.frmDetail.id.value = id;
	document.frmDetail.order.value = order;
	document.frmDetail.action = "lab-detail.php";
	document.frmDetail.submit();
	return false;
}
