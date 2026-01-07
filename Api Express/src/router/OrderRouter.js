import Router from "express"
import { getWithToken, postWithToken, putWithToken, deleteWithToken } from "../service/axiosService.js"
import { responseToDTO } from "../service/responseService.js"

const orderRouter = Router()


///orders?sortfield=t.rowid&sortorder=ASC&limit=100&page=0
orderRouter.get("/", async (req,res)=>{
    const token = req.get('DOLAPIKEY')
    const pageNbr = req.query.page || 0
    const thirdparty_ids = req.query.thirdparty_ids
    try{
        let url =`/orders?sortfield=t.rowid&sortorder=ASC&limit=100&page=${pageNbr}`
        if(thirdparty_ids){
            url +=`&thirdparty_ids=${thirdparty_ids}`
        }
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

orderRouter.get("/:id", async (req,res)=>{
    const token = req.get('DOLAPIKEY')
    const id = req.params.id
    try{
        const response = await getWithToken(`/orders/${id}`,token)
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

orderRouter.post("/", async (req,res)=>{
    const token = req.get('DOLAPIKEY')
    try{
        const response = await postWithToken("/orders", req.body, token)
        res.send({
            status:"ok",
            result: response
        })
    }catch(error){
        res.status(500).send({
            status:"error",
            message: error.message
        })
    }
})

orderRouter.put("/:id", async (req,res)=>{
    const token = req.get('DOLAPIKEY')
    try{
        const response = await putWithToken(`/orders/${req.params.id}`, req.body, token)
        res.send({
            status:"ok",
            result: response
        })
    }catch(error){
        res.status(500).send({
            status:"error",
            message: error.message
        })
    }
})

orderRouter.delete("/:id", async (req,res)=>{
    const token = req.get('DOLAPIKEY')
    try{
        const response = await deleteWithToken(`/orders/${req.params.id}`, token)
        res.send({
            status:"ok",
            result: response
        })
    }catch(error){
        res.status(500).send({
            status:"error",
            message: error.message
        })
    }
})

export default orderRouter
