import Router from "express"
import { getWithToken } from "../service/axiosService.js"
import { responseToDTO } from "../service/responseService.js"

const productRouter = Router()

productRouter.get("/", async (req,res)=>{
        const token = req.get('DOLAPIKEY')
        const pageNbr = req.query.page
        const mode = req.query.mode
        const category = req.query.category
        try{
            let url =`/products?sortfield=t.ref&sortorder=ASC&limit=100&page=${pageNbr}`
            if(mode){
                url += `&mode=${mode}`
            }
            if(category){
                url += `&category=${category}`
            }
            
            const response = await getWithToken(url,token)
            res.send({
                    status:"ok",
                    result: responseToDTO(response,null)
                })
        }catch(error){
            console.log(error);
            res.send({
                status:error
            })
        }
})

productRouter.get("/:id", async (req,res)=>{
        const token = req.get('DOLAPIKEY')
        const id = req.params.id
        try{
            let url =`/products/${id}` 
            const response = await getWithToken(url,token)
            res.send({
                    status:"ok",
                    result: responseToDTO(response,null)
                })
        }catch(error){
            console.log(error);
            res.send({
                status:"error"
            })
        }
})

productRouter.get("/:id/stock", async (req,res)=>{
        const token = req.get('DOLAPIKEY')
        const id = req.params.id
        try{
            let url =`/products/${id}/stock` 
            const response = await getWithToken(url,token)
            res.send({
                    status:"ok",
                    result: responseToDTO(response,null)
                })
        }catch(error){
            console.log(error);
            res.send({
                status:"error"
            })
        }
})

productRouter.get("/products/barcode/:barcode", async (req,res)=>{
        const token = req.get('DOLAPIKEY')
        const barcode = req.params.barcode
        try{
            let url =`/products/barcode/${barcode}`
            const response = await getWithToken(url,token)
            res.send({
                    status:"ok",
                    result: responseToDTO(response,null)
                })
        }catch(error){
            console.log(error);
            res.send({
                status:"error"
            })
        }
})

productRouter.get("/categories/list", async (req,res)=>{
        const token = req.get('DOLAPIKEY')
        const type = req.query.type || 0  // 0 = product categories
        try{
            let url =`/categories?sortfield=t.rowid&sortorder=ASC&limit=0&type=${type}`
            const response = await getWithToken(url,token)
            res.send({
                    status:"ok",
                    result: responseToDTO(response,null)
                })
        }catch(error){
            console.log(error);
            res.send({
                status:"error"
            })
        }
})

export default productRouter