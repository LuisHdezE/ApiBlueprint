from pathlib import Path

script = Path('scripts/u0-6-products-list.py').read_text()

old_sentence = "`products.show` se exporta como vertical slice ejecutable cuando está seleccionado. Los demás endpoints conservan HTTP 501 hasta que su receta ejecutable sea incorporada. Los endpoints y capacidades no seleccionados no se incluyen como infraestructura dormida."
new_sentence = "`products.list` y `products.show` se exportan como vertical slices ejecutables cuando están seleccionados. Los demás endpoints conservan HTTP 501 hasta que su receta ejecutable sea incorporada. Los endpoints y capacidades no seleccionados no se incluyen como infraestructura dormida."

script = script.replace(
    '"""' + old_sentence + '\n""",\n"""' + new_sentence + '\n""",',
    '"""' + old_sentence + '""",\n"""' + new_sentence + '""",',
)

exec(compile(script, 'scripts/u0-6-products-list.py', 'exec'))
