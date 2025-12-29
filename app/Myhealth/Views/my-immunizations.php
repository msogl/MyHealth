<?php
/**
 * Return Vaccine Information Statement (VIS)
 */
function VIS(string $immunization): string
{
	$imm = strtoupper($immunization);
	$docs = '';

	if (str_contains($imm, "DTAP")) {
		$docs = "vis-dtap.pdf|vis-dtap_espanol.pdf|polish_dtap.pdf";
	}
	elseif (str_contains($imm, "HEPA") || str_contains($imm, "HEPATITIS A")) {
		$docs = "vis-hep-a.pdf|vis-hep-a_espanol.pdf";
	}
	elseif (str_contains($imm, "HEPB") || str_contains($imm, "HEPATITIS B")) {
		$docs = "vis-hep-b.pdf|vis-hep-b_espanol.pdf";
	}
	elseif (str_contains($imm, "HIB")) {
		$docs = "vis-hib.pdf|vis-hib_espanol.pdf|po_hib98.pdf";
	}
	elseif (str_contains($imm, "INFLUENZA") || str_contains($imm, "FLU")) {
		$docs = "vis-flu.pdf|vis-flu_espanol.pdf|vis-flu_polish.pdf";
	}
	elseif (str_contains($imm, "IPV")) {
		$docs = "vis-ipv.pdf|vis-ipv_espanol.pdf";
	}
	elseif (str_contains($imm, "MMR")) {
		$docs = "vis-mmr.pdf|vis-mmr_espanol.pdf";
	}
	elseif (str_contains($imm, "PCV")) {
		$docs = "vis-pcv.pdf|vis-pcv_espanol.pdf";
	}
	elseif (str_contains($imm, "ROTAVIRUS") || str_contains($imm, "RV")) {
		$docs = "vis-rotavirus.pdf|vis-rotavirus_espanol.pdf";
	}
	elseif (str_contains($imm, "VARICELLA")) {
		$docs = "vis-varicella.pdf|vis-varicella_espanol.pdf";
	}

	$arr = explode('|', $docs);
    $links = [];

	for($ix=0; $ix<count($arr); $ix++) {
		$link = '<a href="docs/'.$arr[$ix].'" target="_blank">';
		if ($ix == 0) {
			$link .= "English";
		}
		elseif ($ix == 1) {
			$link .= "Espa&ntilde;ol";
		}
		elseif ($ix == 2) {
			$link .= "Polish";
		}
		$link .= "</a>";
        $links[] = $link;
	}

    // return pipe-separated set of links
	return implode(' | ', $links);
}
?>
<div id="main-container">
    <div class="left-side">
        <h1>My Immunizations</h1>
    </div>
    <div class="right-side right no-print">
        <button type="button" class="button" onclick="window.print();"><i class="fa fa-print"></i> Print</button>
    </div>
    <div class="clear"></div>
    <p id="track-message"></p>
    <div class="no-print" style="padding-bottom: 2em;">
        <?php if ($age >= 0 && $age <= 6) { ?>
        <a href="docs/parent-ver-sch-0-6yrs.pdf" target="_blank">Recommended Immunizations for Children from Birth Through 6 Years Old</a><br />
        <a href="docs/parent-ver-sch-0-6yrs-sp.pdf" target="_blank">Vacunas recomendadas para ni&ntilde;os, desde el nacimiento hasta los 6 a&ntilde;os de edad</a>
        <?php }
        elseif ($age >= 7 && $age <= 18) { ?>
        <a href="docs/parent-ver-sch-0-6yrs.pdf" target="_blank">Recommended Immunizations for Children from 7 Through 18 Years Old</a>
        <?php }
        elseif ($age > 18) { ?>
        <a href="docs/adult-schedule-easy-read.pdf" target="_blank">Recommended Immunizations for Adults</a>
        <?php } ?>
    </div>
    <div class="data-table">
        <table id="my-immunizations" class="sortable">
            <tr>
                <th class="center nowrap sort_mmdd">Immunization Date</th>
                <th class="left">Description</th>
                <th class="left">More Information</th>
            </tr>

            <?php foreach($immunizations as $immunization) { ?>
            <tr>
                <td align="center"><?=_WDate($immunization->immunization_date)?></td>
                <td><?=_W($immunization->description)?></td>
                <td><?=VIS($immunization->description)?></td>
            </tr>
            <?php } ?>
        </table>
    </div>

    <?php if (in_array($client, ['RPPG','RPA'])) { ?>
    <div class="no-print">
        <br />
        <p class="mb-1">Have you had a flu shot that's not recorded here? Let us know! Enter the date of your flu shot below.</p>
        <p class="entry wrap">
            <label>Flu shot date:</label>
            <input type="date" id="flu-shot-date" value="" size="10" maxlength="10">
            <button type="button" id="save-btn" class="button">Save</button>
        </p>
    </div>
    <?php } ?>
</div>

<script src="<?= _asset('js/views/Immunization.js') ?>"></script>