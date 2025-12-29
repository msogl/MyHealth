<div id="main-container">
    <h1>Doctor Search</h1>
    <p class="<?=(!empty($errormsg) ? 'error left' : 'hidden')?>"><?=_W($errormsg)?></p>
    <section <?=(!empty($errormsg) ? ' class="hidden"' : '')?>>
        <p id="track-message"></p>
        <div class="form-inline">
            <label>Last name:</label>
            <input type="text" id="lastname" maxlength="60" value="" style="max-width:220px;">
        </div>
        <div class="form-inline">
            <label>First name:</label>
            <input type="text" id="firstname" maxlength="60" value="" style="max-width:220px;">
        </div>
        <div class="form-inline">
            <label>Specialty:</label>
            <select id="specialty" style="min-width:220px;max-width:calc(100% - 100px - 2em);">
                <option value="">-- select --</option>
                <option value="PCP">Primary Care Physician</option>
            </select>
        </div>
        <div class="form-inline">
            <label>Language:</label>
            <select id="language" style="max-width:calc(100% - 100px - 2em);">
                <option value="">-- select --</option>
                <option>English</option>
                <option>Polish</option>
                <option>Spanish</option>
            </select>
        </div>
        <div class="form-inline">
            <label>City:</label>
            <input type="text" id="city" maxlength="50" style="max-width:220px;">
        </div>
        <div class="form-inline">
            <label>Zip Code:</label>
            <input type="text" id="zip" maxlength="5" style="width:80px;">
        </div>
        <div class="form-inline">
            <label>Within:</label>
            <select id="radius">
                <option value="0">0</option>
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
                <option value="5" selected>5</option>
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="20">20</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            miles
        </div>
        <div class="form-inline gender-container">
            <label>Gender:</label>
            <input type="radio" id="gender-male" name="gender" value="M">
            <label for="gender-male">Male</label>
            &nbsp;&nbsp;
            <input type="radio" id="gender-female" name="gender" value="F">
            <label for="gender-female">Female</label>
            &nbsp;&nbsp;
            <input type="radio" id="gender-any" name="gender" value="">
            <label for="gender-any">Any</label>
        </div>
        <div class="form-inline">
            <label style="vertical-align:top;">Hours:</label>
            <div style="display:inline-block;">
                <input type="checkbox" id="morning-hours" name="hours">
                <label for="morning-hours">Morning hours (before 9am)</label>
                <br/>
                <input type="checkbox" id="evening-hours" name="hours">
                <label for="evening-hours">Evening hours (after 5pm)</label>
                <br/>
                <input type="checkbox" id="weekend-hours" name="hours">
                <label for="weekend-hours">Weekend hours</label>
            </div>
        </div>
        <div class="form-inline top-pad1">
            <label>&nbsp;</label>
            <button type="button" id="search-btn" class="button">Search</button>
        </div>
    </section>

    <div id="results">
    </div>
</div>

<link rel="stylesheet" type="text/css" href="<?= _asset('css/provsearch.css') ?>">
<script src="<?= _asset('js/views/DoctorSearch.js') ?>"></script>
<script>
    DoctorSearch.getSpecialties();
</script>