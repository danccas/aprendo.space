          <div class="card shadow mb-4">
            <div class="card-header py-3">
              <h6 class="m-0 font-weight-bold text-primary">Relación de Formularios</h6>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <?= Tablefy::getInstance('listado')->render(array()); ?>
              </div>
            </div>
          </div>
