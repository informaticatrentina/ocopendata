<div class="row">
    <div class="col-md-12">
        <h1>Importa contenuto remoto</h1>
        <div class="panel panel-info">
            <div class="panel-body">
                {if $error}
                    <div class="alert alert-warning">
                        {$error|wash()}
                    </div>
                {/if}

                <form method="post" action="{'opendata/import'|ezurl(no)}" class="form-horizontal">
                    <div class="form-group">
                        <label for="CurrentRemoteBaseUrl" class="col-sm-2 control-label">Remote base
                            url</label>

                        <div class="col-sm-10">
                            <input type="text" name="CurrentRemoteBaseUrl" id="CurrentRemoteBaseUrl"
                                   class="form-control"
                                   value="{$CurrentRemoteBaseUrl}"/>

                            <p class="help-block">Ad esempio: http://www.opencontent.it</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="CurrentRemoteNodeId" class="col-sm-2 control-label">Id Nodo
                            remoto</label>

                        <div class="col-sm-10">
                            <input type="text" name="CurrentRemoteNodeId" id="CurrentRemoteNodeId"
                                   class="form-control"
                                   value="{$CurrentRemoteNodeId}"/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="CurrentLocalParentNodeId" class="col-sm-2 control-label">Importa
                            in...</label>

                        <div class="col-sm-10">
                            {if $CurrentLocalParentNode}
                                <a href="{$CurrentLocalParentNode.url_alias|ezurl(no)}"
                                   target="_blank">{$CurrentLocalParentNode.name|wash()}</a>
                            {/if}
                            <input type="hidden" name="CurrentLocalParentNodeId"
                                   value="{$CurrentLocalParentNodeId}"/>
                            <button type="submit" class="btn btn-default" name="SelectCurrentLocalParentNodeId">Seleziona</button>

                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-10">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="CreateContentClass" {if $CreateContentClass}checked="checked"{/if} /> Crea la classe di contenuto se non esiste
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-10">
                            <button type="submit" class="btn btn-primary pull-right" name="DoImport">Importa</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>