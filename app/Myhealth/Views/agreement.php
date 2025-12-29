<style>
    #headerlinks {
        display: none;
    }
</style>

<div id="agreement" class="mt-2" style="height:calc(100dvh - (106px + 36px + 6rem));overflow-y:auto;">
    <?=$contents?>
</div>

<div style="text-align:center;">
    <br/>
    <form name="agreement" action="agreement" method="POST">
        <?=_csrf()?>
        <input type="hidden" name="a" value="">
        <p class="submit" style="text-align:center;">
            <button type="button" name="btnYes" class="button" onclick="agree('Y');" style="width:140px;">I Agree</button>
            <button type="button" name="btnNo" class="button" onclick="agree('N');" style="width:140px;">I Do Not Agree</button>
        </p>
    </form>
</div>

<br/><br/><br/>

<script>
function agree(yesNo)
{
    document.agreement.a.value = yesNo;
    document.agreement.submit();
}
</script>
