<div id="filter-projects" class="entity-filter clearfix" ng-show="data.global.filterEntity === 'project'">
    <header class="clearfix">
        <a href="<?php echo $app->getBaseUrl() ?>" class="icon icon-go-back"></a>
        Projetos
        <a class="icon icon-show-advanced-search" ng-click="toggleAdvancedFilters()"></a>
    </header>
    <div ng-show="!collapsedFilters && !showInfobox()">
        <form class="form-palavra-chave filter search-filter--keyword">
            <label for="palavra-chave-evento">Palavra-chave</label>
            <input ng-model="data.project.keyword" class="search-field" type="text" name="palavra-chave-evento" placeholder="Buscar projetos" />
        </form>
        <!--.filter-->
        <div class="filter">
            <span class="label search-filter--tipo">Tipo</span>
            <div class="dropdown">
                <div class="placeholder">Selecione os tipos</div>
                <div class="submenu-dropdown">
                    <ul class="filter-list">
                        <li ng-repeat="type in types.project" ng-class="{'selected':isSelected(data.project.types, type.id)}" ng-click="toggleSelection(data.project.types, type.id)">
                            <span>{{type.name}}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <!--.filter-->
        <div class="filter search-filter--inscricoes-abertas">
            <span class="icon icon-check" ng-class="{'selected': data.project.ropen}" ng-click="data.project.ropen = !data.project.ropen"></span>
            <span class="label show-label" ng-click="data.project.ropen = !data.project.ropen">Inscrições Abertas</span>
        </div>
        <!--.filter-->
        <div class="filter verified-filter search-filter--verified">
            <a class="hltip btn btn-verified" ng-class="{'selected':data.project.isVerified}" title="Exibir somente resultados verificados" ng-click="toggleVerified('project')"><?php $this->dict('search: verified results') ?></a>
        </div>
        <!-- div.verified-filter -->
    </div>
</div>
<!--#filter-projects-->
