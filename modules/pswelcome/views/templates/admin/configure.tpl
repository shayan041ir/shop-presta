<div class="panel">
  <h3>{l s='Welcome Message Settings' mod='pswelcome'}</h3>
  <form method="post">
    <div class="form-group">
      <label>{l s='Welcome Message' mod='pswelcome'}</label>
      <input type="text" name="PSWELCOME_MSG" value="{$welcome_msg}" class="form-control" />
    </div>
    <button type="submit" name="submit_pswelcome" class="btn btn-primary">
      {l s='Save' mod='pswelcome'}
    </button>
  </form>
</div>
